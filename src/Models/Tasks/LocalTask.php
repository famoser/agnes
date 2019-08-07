<?php


namespace Agnes\Models\Tasks;

use Agnes\Services\TaskExecutionService;

class LocalTask extends Task
{
    /**
     * @param TaskExecutionService $service
     * @throws \Exception
     */
    public function execute(TaskExecutionService $service)
    {
        $service->executeLocal($this);
    }
}