<?php

namespace app\controllers;

use app\models\Tender;
use app\models\TenderFile;
use app\models\User;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class TenderController extends ApiController
{
    public function actionIndex(): array
    {
        $user = $this->getAuthUser();
        $companyId = $this->getSelectedCompanyId(false);
        $query = Tender::find()->with(['user', 'company']);

        if ($companyId) {
            $query->andWhere(['company_id' => $companyId]);
        }

        if ($user->role !== User::ROLE_SUPERADMIN) {
            $query->andWhere(['user_id' => $user->id]);
        }

        return [
            'tenders' => $query->orderBy(['id' => SORT_DESC])->all(),
        ];
    }

    public function actionCreate(): array
    {
        $user = $this->getAuthUser();
        $companyId = $this->getSelectedCompanyId();
        $request = \Yii::$app->request;
        $input = $request->getBodyParams();

        if (empty($input)) {
            $input = $request->post();
        }

        $model = new Tender();
        $model->load($input, '');
        $model->user_id = $user->id;
        $model->company_id = $companyId;
        $model->created_date = date('Y-m-d');
        $model->status = $model->status ?: Tender::STATUS_OPEN;

        $uploadedFiles = UploadedFile::getInstancesByName('files');
        $savedFilePaths = [];
        $transaction = \Yii::$app->db->beginTransaction();

        try {
            if (!$model->save()) {
                \Yii::$app->response->statusCode = 422;
                return ['message' => 'Tender creation failed.', 'errors' => $model->errors];
            }

            $tenderFiles = $this->saveTenderFiles($model, $user, $uploadedFiles, $savedFilePaths);
            $transaction->commit();

            return [
                'message' => 'Tender created successfully.',
                'tender' => $model,
                'files' => $tenderFiles,
            ];
        } catch (\RuntimeException $exception) {
            $transaction->rollBack();
            foreach ($savedFilePaths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            \Yii::$app->response->statusCode = 422;
            return ['message' => $exception->getMessage()];
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            foreach ($savedFilePaths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            throw $exception;
        }
    }

    public function actionView(int $id): array
    {
        $tender = $this->findTender($id, true);

        return [
            'tender' => $tender,
            'files' => $tender->files,
        ];
    }

    public function actionUpdate(int $id): array
    {
        $tender = $this->findTender($id, true);
        $user = $this->getAuthUser();
        $request = \Yii::$app->request;
        $input = $request->getBodyParams();

        if (empty($input)) {
            $input = $request->post();
        }

        $tender->load($input, '');
        $uploadedFiles = UploadedFile::getInstancesByName('files');
        $savedFilePaths = [];
        $transaction = \Yii::$app->db->beginTransaction();

        try {
            if (!$tender->save()) {
                \Yii::$app->response->statusCode = 422;
                return ['message' => 'Tender update failed.', 'errors' => $tender->errors];
            }

            $newFiles = $this->saveTenderFiles($tender, $user, $uploadedFiles, $savedFilePaths);
            $transaction->commit();

            return [
                'message' => 'Tender updated successfully.',
                'tender' => $tender,
                'files' => $tender->files,
                'new_files' => $newFiles,
            ];
        } catch (\RuntimeException $exception) {
            $transaction->rollBack();
            foreach ($savedFilePaths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            \Yii::$app->response->statusCode = 422;
            return ['message' => $exception->getMessage()];
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            foreach ($savedFilePaths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            throw $exception;
        }
    }

    public function actionUpdateStatus(int $id): array
    {
        $tender = $this->findTender($id);
        $tender->status = \Yii::$app->request->bodyParams['status'] ?? $tender->status;

        if (!$tender->save()) {
            \Yii::$app->response->statusCode = 422;
            return ['message' => 'Tender status update failed.', 'errors' => $tender->errors];
        }

        return ['message' => 'Tender status updated.', 'tender' => $tender];
    }

    private function saveTenderFiles(Tender $tender, User $user, array $uploadedFiles, array &$savedFilePaths = []): array
    {
        if (empty($uploadedFiles)) {
            return [];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip', 'rar'];
        $uploadDir = \Yii::getAlias('@app/web/uploads/tenders/' . $tender->id);
        FileHelper::createDirectory($uploadDir);
        $savedFiles = [];

        foreach ($uploadedFiles as $uploadedFile) {
            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }

            $extension = strtolower((string) $uploadedFile->extension);
            if (!in_array($extension, $allowedExtensions, true)) {
                throw new \RuntimeException('Unsupported file type: ' . $uploadedFile->name);
            }

            if ((int) $uploadedFile->size > 20 * 1024 * 1024) {
                throw new \RuntimeException('File too large: ' . $uploadedFile->name);
            }

            $storedName = uniqid('tender_', true) . ($extension ? '.' . $extension : '');
            $absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

            if (!$uploadedFile->saveAs($absolutePath)) {
                throw new \RuntimeException('Failed to upload file: ' . $uploadedFile->name);
            }

            $savedFilePaths[] = $absolutePath;
            $fileModel = new TenderFile();
            $fileModel->tender_id = $tender->id;
            $fileModel->user_id = $user->id;
            $fileModel->original_name = $uploadedFile->name;
            $fileModel->stored_name = $storedName;
            $fileModel->file_path = 'uploads/tenders/' . $tender->id . '/' . $storedName;
            $fileModel->file_type = $uploadedFile->type ?: $extension;
            $fileModel->file_size = (int) $uploadedFile->size;

            if (!$fileModel->save()) {
                throw new \RuntimeException('File record save failed for: ' . $uploadedFile->name);
            }

            $savedFiles[] = $fileModel;
        }

        return $savedFiles;
    }

    private function findTender(int $id, bool $withRelations = false): Tender
    {
        $user = $this->getAuthUser();
        $companyId = $this->getSelectedCompanyId(false);
        $query = Tender::find();

        if ($withRelations) {
            $query->with(['user', 'company', 'files']);
        }

        $query->where(['id' => $id]);
        if ($companyId) {
            $query->andWhere(['company_id' => $companyId]);
        }

        $tender = $query->one();

        if (!$tender) {
            throw new NotFoundHttpException('Tender not found.');
        }

        if ($user->role !== User::ROLE_SUPERADMIN && (int) $tender->user_id !== (int) $user->id) {
            throw new NotFoundHttpException('Tender not found.');
        }

        return $tender;
    }
}