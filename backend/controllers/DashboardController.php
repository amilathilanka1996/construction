<?php

namespace app\controllers;

use app\models\Project;
use app\models\ProjectExpense;
use app\models\ProjectIncome;
use app\models\Tender;
use app\models\User;
use yii\db\Expression;

class DashboardController extends ApiController
{
    public function actionIndex(): array
    {
        $user = $this->getAuthUser();
        $companyId = $this->getSelectedCompanyId(false);
        $requestedStatuses = \Yii::$app->request->get('statuses', []);

        if (is_string($requestedStatuses) && $requestedStatuses !== '') {
            $requestedStatuses = array_map('trim', explode(',', $requestedStatuses));
        }

        if (!is_array($requestedStatuses)) {
            $requestedStatuses = [];
        }

        $allowedStatuses = [
            Project::STATUS_RUNNING,
            Project::STATUS_RETENTION,
            Project::STATUS_CLOSED,
        ];
        $selectedStatuses = array_values(array_intersect($allowedStatuses, $requestedStatuses));

        $projectQuery = Project::find();
        $filteredProjectQuery = Project::find();
        $expenseQuery = ProjectExpense::find()
            ->alias('expense')
            ->innerJoin(['project' => Project::tableName()], 'project.id = expense.project_id');
        $incomeQuery = ProjectIncome::find()
            ->alias('income')
            ->innerJoin(['project' => Project::tableName()], 'project.id = income.project_id');
        $tenderQuery = Tender::find()->andWhere(['status' => Tender::STATUS_OPEN]);

        if ($companyId) {
            $projectQuery->andWhere(['company_id' => $companyId]);
            $filteredProjectQuery->andWhere(['company_id' => $companyId]);
            $expenseQuery->andWhere(['project.company_id' => $companyId]);
            $incomeQuery->andWhere(['project.company_id' => $companyId]);
            $tenderQuery->andWhere(['company_id' => $companyId]);
        }

        if ($user->role !== User::ROLE_SUPERADMIN) {
            $projectQuery->andWhere(['user_id' => $user->id]);
            $filteredProjectQuery->andWhere(['user_id' => $user->id]);
            $expenseQuery->andWhere(['expense.user_id' => $user->id]);
            $incomeQuery->andWhere(['income.user_id' => $user->id]);
            $tenderQuery->andWhere(['user_id' => $user->id]);
        }

        if (!empty($selectedStatuses)) {
            $filteredProjectQuery->andWhere(['status' => $selectedStatuses]);
            $expenseQuery->andWhere(['project.status' => $selectedStatuses]);
            $incomeQuery->andWhere(['project.status' => $selectedStatuses]);
        }

        $statusRows = (clone $projectQuery)
            ->select(['status', 'count' => new Expression('COUNT(*)')])
            ->groupBy(['status'])
            ->asArray()
            ->all();

        $totalBidSecurityDeposit = (float) $tenderQuery->sum('bid_security_deposit');
        $totalPerformanceSecurityDeposit = (float) (clone $tenderQuery)->sum('performance_security_deposit');
        $expenseTotal = (float) $expenseQuery->sum('expense.amount');
        $incomeTotal = (float) $incomeQuery->sum('income.amount');

        return [
            'summary' => [
                'project_count' => (int) $filteredProjectQuery->count(),
                'running_count' => (int) (clone $filteredProjectQuery)->andWhere(['status' => Project::STATUS_RUNNING])->count(),
                'retention_count' => (int) (clone $filteredProjectQuery)->andWhere(['status' => Project::STATUS_RETENTION])->count(),
                'closed_count' => (int) (clone $filteredProjectQuery)->andWhere(['status' => Project::STATUS_CLOSED])->count(),
                'expense_total' => $expenseTotal,
                'income_total' => $incomeTotal,
                'balance_total' => $incomeTotal - $expenseTotal,
                'tender_total' => $totalBidSecurityDeposit + $totalPerformanceSecurityDeposit,
            ],
            'selected_statuses' => $selectedStatuses,
            'status_breakdown' => $statusRows,
            'recent_projects' => $projectQuery->orderBy(['id' => SORT_DESC])->limit(5)->all(),
        ];
    }
}