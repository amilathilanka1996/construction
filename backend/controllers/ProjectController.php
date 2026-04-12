<?php

namespace app\controllers;

use app\models\Project;
use app\models\ProjectExpense;
use app\models\ProjectFile;
use app\models\ProjectIncome;
use app\models\User;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class ProjectController extends ApiController
{
    public function actionIndex(): array
    {
        $user = $this->getAuthUser();
        $companyId = $this->getSelectedCompanyId(false);
        $query = Project::find()->with(['user', 'company']);

        if ($companyId) {
            $query->andWhere(['company_id' => $companyId]);
        }

        if ($user->role !== User::ROLE_SUPERADMIN) {
            $query->andWhere(['user_id' => $user->id]);
        }

        return [
            'projects' => $query->orderBy(['id' => SORT_DESC])->all(),
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

        $model = new Project();
        $model->load($input, '');
        $model->user_id = $user->id;
        $model->company_id = $companyId;
        $model->created_date = date('Y-m-d');
        $model->status = $model->status ?: Project::STATUS_RUNNING;
        $model->status_changed_at = date('Y-m-d H:i:s');

        $uploadedFiles = UploadedFile::getInstancesByName('files');
        $savedFilePaths = [];
        $transaction = \Yii::$app->db->beginTransaction();

        try {
            if (!$model->save()) {
                \Yii::$app->response->statusCode = 422;
                return ['message' => 'Project creation failed.', 'errors' => $model->errors];
            }

            $projectFiles = $this->saveProjectFiles($model, $user, $uploadedFiles, $savedFilePaths);
            $transaction->commit();

            return [
                'message' => 'Project created successfully.',
                'project' => $model,
                'files' => $projectFiles,
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
        $project = $this->findProject($id, true);

        return [
            'project' => $project,
            'expenses' => $project->expenses,
            'incomes' => $project->incomes,
            'files' => $project->files,
        ];
    }

    public function actionUpdate(int $id): array
    {
        $project = $this->findProject($id, true);
        $user = $this->getAuthUser();
        $request = \Yii::$app->request;
        $input = $request->getBodyParams();

        if (empty($input)) {
            $input = $request->post();
        }

        $project->load($input, '');
        $uploadedFiles = UploadedFile::getInstancesByName('files');
        $savedFilePaths = [];
        $transaction = \Yii::$app->db->beginTransaction();

        try {
            if (!$project->save()) {
                \Yii::$app->response->statusCode = 422;
                return ['message' => 'Project update failed.', 'errors' => $project->errors];
            }

            $projectFiles = $this->saveProjectFiles($project, $user, $uploadedFiles, $savedFilePaths);
            $transaction->commit();

            return [
                'message' => 'Project updated successfully.',
                'project' => $project,
                'files' => $project->files,
                'new_files' => $projectFiles,
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
        $project = $this->findProject($id);
        $project->status = \Yii::$app->request->bodyParams['status'] ?? $project->status;
        $project->status_changed_at = date('Y-m-d H:i:s');

        if (!$project->save()) {
            \Yii::$app->response->statusCode = 422;
            return ['message' => 'Project status update failed.', 'errors' => $project->errors];
        }

        return ['message' => 'Project status updated.', 'project' => $project];
    }

    public function actionAddExpense(int $id): array
    {
        $project = $this->findProject($id);
        $user = $this->getAuthUser();
        $body = \Yii::$app->request->bodyParams;
        $items = $body['items'] ?? [$body];

        if (empty($items) || !is_array($items)) {
            \Yii::$app->response->statusCode = 422;
            return ['message' => 'Expense entry failed.', 'errors' => ['items' => ['At least one expense item is required.']]];
        }

        $transaction = \Yii::$app->db->beginTransaction();
        $createdExpenses = [];

        try {
            foreach ($items as $index => $item) {
                $model = new ProjectExpense();
                $model->load(is_array($item) ? $item : [], '');
                $model->project_id = $project->id;
                $model->user_id = $user->id;
                $model->entry_date = $model->entry_date ?: date('Y-m-d');

                if (!$model->save()) {
                    $transaction->rollBack();
                    \Yii::$app->response->statusCode = 422;
                    return [
                        'message' => 'Expense entry failed.',
                        'errors' => [
                            'item' => $index + 1,
                            'fields' => $model->errors,
                        ],
                    ];
                }

                $createdExpenses[] = $model;
            }

            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        return [
            'message' => count($createdExpenses) > 1 ? 'Expenses added successfully.' : 'Expense added successfully.',
            'expenses' => $createdExpenses,
        ];
    }

    public function actionAddIncome(int $id): array
    {
        $project = $this->findProject($id);
        $user = $this->getAuthUser();
        $model = new ProjectIncome();
        $model->load(\Yii::$app->request->bodyParams, '');
        $model->project_id = $project->id;
        $model->user_id = $user->id;

        if (!$model->save()) {
            \Yii::$app->response->statusCode = 422;
            return ['message' => 'Income entry failed.', 'errors' => $model->errors];
        }

        return ['message' => 'Income added successfully.', 'income' => $model];
    }

    private function saveProjectFiles(Project $project, User $user, array $uploadedFiles, array &$savedFilePaths = []): array
    {
        if (empty($uploadedFiles)) {
            return [];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip', 'rar'];
        $uploadDir = \Yii::getAlias('@app/web/uploads/projects/' . $project->id);
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

            $storedName = uniqid('project_', true) . ($extension ? '.' . $extension : '');
            $absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

            if (!$uploadedFile->saveAs($absolutePath)) {
                throw new \RuntimeException('Failed to upload file: ' . $uploadedFile->name);
            }

            $savedFilePaths[] = $absolutePath;
            $fileModel = new ProjectFile();
            $fileModel->project_id = $project->id;
            $fileModel->user_id = $user->id;
            $fileModel->original_name = $uploadedFile->name;
            $fileModel->stored_name = $storedName;
            $fileModel->file_path = 'uploads/projects/' . $project->id . '/' . $storedName;
            $fileModel->file_type = $uploadedFile->type ?: $extension;
            $fileModel->file_size = (int) $uploadedFile->size;

            if (!$fileModel->save()) {
                throw new \RuntimeException('File record save failed for: ' . $uploadedFile->name);
            }

            $savedFiles[] = $fileModel;
        }

        return $savedFiles;
    }

    private function findProject(int $id, bool $withRelations = false): Project
    {
        $user = $this->getAuthUser();
        $companyId = $this->getSelectedCompanyId(false);
        $query = Project::find();

        if ($withRelations) {
            $query->with(['user', 'company', 'expenses', 'incomes', 'files']);
        }

        $query->where(['id' => $id]);
        if ($companyId) {
            $query->andWhere(['company_id' => $companyId]);
        }

        $project = $query->one();
        if (!$project) {
            throw new NotFoundHttpException('Project not found.');
        }

        if ($user->role !== User::ROLE_SUPERADMIN && (int) $project->user_id !== (int) $user->id) {
            throw new NotFoundHttpException('Project not found.');
        }

        return $project;
    }
}