<?php

require_once AMFPHP_ROOTPATH . "Helpers/quest_helper.php";

function trackQuestProgress($uid, $action, $type, $amount = 1, $extraData = []) {
    $activeQuests = getActiveQuests($uid);
    $updatedQuests = [];

    foreach ($activeQuests as $questName => $questState) {
        $quest = getQuestByName($questName);
        if (!$quest || empty($quest['tasks'])) {
            continue;
        }

        foreach ($quest['tasks'] as $taskIndex => $task) {
            $taskAction = $task['action'] ?? '';
            $taskType = $task['type'] ?? '';

            if (!matchesTaskAction($taskAction, $action, $taskType, $type, $extraData)) {
                continue;
            }

            $result = updateQuestProgress($uid, $questName, $taskIndex, $amount);
            if ($result) {
                $updatedQuests[$questName] = $result;

                checkAndCompleteQuest($uid, $questName);
            }
        }
    }

    return $updatedQuests;
}

function matchesTaskAction($taskAction, $playerAction, $taskType, $playerType, $extraData = []) {
    if ($taskAction !== $playerAction) {
        $actionMappings = [
            'harvestByCode' => ['harvest'],
            'harvestByCategory' => ['harvest'],
            'plantCropByCode' => ['plant', 'plantCrop'],
            'plantCropByCategory' => ['plant', 'plantCrop'],
            'plowPlot' => ['plow'],
            'makeRecipeByCode' => ['makeRecipe', 'craft'],
            'buyItemByCode' => ['buyItem', 'purchase'],
            'useItemByCode' => ['useItem', 'use'],
            'getMasteryLevelByCode' => ['mastery', 'getMastery'],
        ];

        $allowedActions = $actionMappings[$taskAction] ?? [];
        if (!in_array($playerAction, $allowedActions)) {
            return false;
        }
    }

    if (!matchesTaskType($taskAction, $taskType, $playerType, $extraData)) {
        return false;
    }

    return true;
}

function matchesTaskType($taskAction, $taskType, $playerType, $extraData = []) {
    if ($taskType === $playerType) {
        return true;
    }

    if (strpos($taskAction, 'ByCategory') !== false) {
        $itemCategories = $extraData['categories'] ?? [];

        $categoryToMatch = $taskType;
        if (strpos($taskType, 'all') === 0) {
            $categoryToMatch = substr($taskType, 3);
        }

        foreach ($itemCategories as $cat) {
            if (strcasecmp($cat, $categoryToMatch) === 0) {
                return true;
            }
        }

        return false;
    }

    return false;
}

function trackHarvestProgress($uid, $obj, $itemName, $itemData = []) {
    $extraData = [
        'itemCode' => $itemName,
        'categories' => $itemData['categories'] ?? [],
        'objState' => $obj['state'] ?? null,
    ];

    $updates1 = trackQuestProgress($uid, 'harvestByCode', $itemName, 1, $extraData);

    $updates2 = trackQuestProgress($uid, 'harvestByCategory', $itemName, 1, $extraData);

    return array_merge($updates1, $updates2);
}

function trackPlantProgress($uid, $itemName, $itemData = []) {
    $extraData = [
        'itemCode' => $itemName,
        'categories' => $itemData['categories'] ?? [],
    ];

    $updates1 = trackQuestProgress($uid, 'plantCropByCode', $itemName, 1, $extraData);

    $updates2 = trackQuestProgress($uid, 'plantCropByCategory', $itemName, 1, $extraData);

    return array_merge($updates1, $updates2);
}

function trackPlowProgress($uid, $count = 1) {
    return trackQuestProgress($uid, 'plowPlot', 'plot', $count);
}

function trackRecipeProgress($uid, $recipeCode, $recipeData = []) {
    $extraData = [
        'recipeCode' => $recipeCode,
        'categories' => $recipeData['categories'] ?? [],
    ];

    return trackQuestProgress($uid, 'makeRecipeByCode', $recipeCode, 1, $extraData);
}

function trackBuyItemProgress($uid, $itemCode, $quantity = 1) {
    return trackQuestProgress($uid, 'buyItemByCode', $itemCode, $quantity);
}

function trackUseItemProgress($uid, $itemCode, $quantity = 1) {
    return trackQuestProgress($uid, 'useItemByCode', $itemCode, $quantity);
}

function trackMasteryProgress($uid, $itemCode, $newLevel) {
    $extraData = [
        'masteryLevel' => $newLevel,
    ];

    return trackQuestProgress($uid, 'getMasteryLevelByCode', $itemCode, 1, $extraData);
}

function trackStoreProgress($uid, $itemCode, $quantity = 1) {
    return trackQuestProgress($uid, 'storeItemByCode', $itemCode, $quantity);
}

function trackDialogView($uid, $dialogId) {
    return trackQuestProgress($uid, 'viewDialog', $dialogId, 1);
}

function trackWorldScoreLevel($uid, $worldType, $currentLevel) {
    $extraData = [
        'worldType' => $worldType,
        'level' => $currentLevel,
    ];

    return trackQuestProgress($uid, 'reachWorldScoreLevel', (string)$currentLevel, 1, $extraData);
}

function trackFeatureCraftingNPC($uid, $npcCode, $featureCode = '') {
    $extraData = [
        'featureCode' => $featureCode,
    ];

    return trackQuestProgress($uid, 'completeFeatureCraftingNPC', $npcCode, 1, $extraData);
}

function trackPlaceItemProgress($uid, $itemCode, $quantity = 1) {
    return trackQuestProgress($uid, 'placeItemByCode', $itemCode, $quantity);
}

function trackCollectFromBuilding($uid, $buildingCode) {
    return trackQuestProgress($uid, 'collectFromBuildingByCode', $buildingCode, 1);
}

function trackBatchProgress($uid, $progressUpdates) {
    $allUpdates = [];

    foreach ($progressUpdates as $update) {
        $action = $update[0] ?? '';
        $type = $update[1] ?? '';
        $amount = $update[2] ?? 1;
        $extraData = $update[3] ?? [];

        $updates = trackQuestProgress($uid, $action, $type, $amount, $extraData);
        $allUpdates = array_merge($allUpdates, $updates);
    }

    return $allUpdates;
}
