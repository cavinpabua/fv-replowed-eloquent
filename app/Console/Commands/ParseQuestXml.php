<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ParseQuestXml extends Command
{
    protected $signature = 'quest:parse {--truncate : Truncate existing quests before import}';
    protected $description = 'Parse questSettings XML and populate quests table';

    public function handle()
    {
        $xmlPath = public_path('farmville/xml/gz/v855038/questSettings_0.xml.gz');

        if (!file_exists($xmlPath)) {
            $this->error("Quest XML file not found: $xmlPath");
            return 1;
        }

        $this->info('Reading quest XML file...');
        $xmlContent = gzuncompress(file_get_contents($xmlPath));

        if ($xmlContent === false) {
            $this->error('Failed to decompress XML file');
            return 1;
        }

        $this->info('Parsing XML...');
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $this->error('Failed to parse XML');
            foreach (libxml_get_errors() as $error) {
                $this->error($error->message);
            }
            return 1;
        }

        if ($this->option('truncate')) {
            $this->info('Truncating existing quests...');
            DB::table('quests')->truncate();
        }

        $quests = $xml->quest;
        $total = count($quests);
        $this->info("Found $total quests to import");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $inserted = 0;
        $skipped = 0;

        foreach ($quests as $quest) {
            $name = (string) $quest['name'];

            // Check if quest already exists
            if (!$this->option('truncate') && DB::table('quests')->where('name', $name)->exists()) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $data = [
                'name' => $name,
                'category' => (string) $quest['category'] ?: null,
                'priority' => (int) ($quest['priority'] ?? 1),
                'replay' => ((string) $quest['replay']) === 'true',
                'skip' => ((string) $quest['skip']) === 'true',
                'kill_quest' => ((string) $quest['kill']) === 'true',
                'mem_store_id' => !empty($quest['memStoreId']) ? (int) $quest['memStoreId'] : null,
                'prereqs' => $this->parsePrereqs($quest->prereqs),
                'children' => $this->parseChildren($quest->children),
                'tasks' => $this->parseTasks($quest->tasks),
                'rewards' => $this->parseRewards($quest->rewards),
                'frontend' => $this->parseFrontend($quest->frontend),
                'friend_reward' => $this->parseFriendReward($quest->friendReward),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('quests')->insert($data);
            $inserted++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Import complete: $inserted inserted, $skipped skipped");

        return 0;
    }

    private function parsePrereqs($prereqs): ?string
    {
        if (!$prereqs || !$prereqs->prereq) {
            return null;
        }

        $result = [];
        foreach ($prereqs->prereq as $prereq) {
            $result[] = [
                'type' => (string) $prereq['type'],
                'value' => (string) $prereq['value'],
            ];
        }

        return json_encode($result);
    }

    private function parseChildren($children): ?string
    {
        if (!$children || !$children->child) {
            return null;
        }

        $result = [];
        foreach ($children->child as $child) {
            $result[] = [
                'type' => (string) $child['type'],
                'value' => (string) $child['value'],
            ];
        }

        return json_encode($result);
    }

    private function parseTasks($tasks): ?string
    {
        if (!$tasks || !$tasks->task) {
            return null;
        }

        $result = [];
        foreach ($tasks->task as $task) {
            $taskData = [];
            foreach ($task->attributes() as $key => $value) {
                $taskData[$key] = (string) $value;
            }
            // Convert total to int
            if (isset($taskData['total'])) {
                $taskData['total'] = (int) $taskData['total'];
            }
            if (isset($taskData['cashValue'])) {
                $taskData['cashValue'] = (int) $taskData['cashValue'];
            }
            $result[] = $taskData;
        }

        return json_encode($result);
    }

    private function parseRewards($rewards): ?string
    {
        if (!$rewards || !$rewards->reward) {
            return null;
        }

        $result = [];
        foreach ($rewards->reward as $reward) {
            $rewardData = [
                'type' => (string) $reward['type'],
                'value' => (string) $reward['value'],
            ];
            if (!empty($reward['quantity'])) {
                $rewardData['quantity'] = (int) $reward['quantity'];
            }
            $result[] = $rewardData;
        }

        return json_encode($result);
    }

    private function parseFrontend($frontend): ?string
    {
        if (!$frontend) {
            return null;
        }

        $result = [];
        foreach ($frontend->children() as $child) {
            $result[$child->getName()] = (string) $child;
        }

        return !empty($result) ? json_encode($result) : null;
    }

    private function parseFriendReward($friendReward): ?string
    {
        if (!$friendReward) {
            return null;
        }

        return json_encode([
            'bundle' => (string) $friendReward['bundle'],
            'item' => (string) $friendReward['item'],
        ]);
    }
}
