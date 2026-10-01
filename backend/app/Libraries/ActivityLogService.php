<?php

namespace App\Libraries;

use App\Enums\ActivityAction;
use App\Enums\UserTypes;
use App\Models\ActivityLogModel;
use InvalidArgumentException;

class ActivityLogService
{
    protected ActivityLogModel $activityLogModel;

    public function __construct()
    {
        $this->activityLogModel = new ActivityLogModel();
    }

    public function logActivity(int $actorId, UserTypes $actorType, ActivityAction $activity, string $description): bool
    {
        $request = service('request');

        $data = [
            'action' => $activity->getValue(),
            'description' => $description,
            'ip_address' => $request->getIpAddress(),
            'user_agent' => (string) $request->getUserAgent(),
        ];

        if ($actorType->equals(UserTypes::CARHRIS())) {
            $data['carhris_emp_id'] = (int) $actorId;
        } else if ($actorType->equals(UserTypes::SYSTEM())) {
            $data['native_admin_id'] = (int) $actorId;
        } else {
            throw new InvalidArgumentException("Unhandled actor type: {$actorType->getValue()}");
        }

        return (bool) $this->activityLogModel->insert($data);
    }
}
