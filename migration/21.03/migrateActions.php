<?php

require '../../vendor/autoload.php';

chdir('../..');

$customs =  scandir('custom');

foreach ($customs as $custom) {
    if (in_array($custom, ['custom.json', 'custom.xml', '.', '..'])) {
        continue;
    }

    \SrcCore\models\DatabasePDO::reset();
    new \SrcCore\models\DatabasePDO(['customId' => $custom]);

    $migrated = 0;

    $actionsToMigrate = ['sendExternalSignatoryBookAction', 'sendSignatureBookAction', 'continueVisaCircuitAction'];

    $actions = \Action\models\ActionModel::get([
        'select' => ['id', 'parameters', 'id_status'],
        'where'  => ['component in (?)'],
        'data'   => [$actionsToMigrate]
    ]);

    foreach ($actions as $key => $action) {
        $parameters = json_decode($action['parameters'], true);
        $parameters['successStatus'] = $action['id_status'];
        $parameters =  json_encode($parameters);

        \Action\models\ActionModel::update([
            'set'   => ['parameters' => $parameters],
            'where' => ['id = ?'],
            'data'  => [$action['id']]
        ]);
        $migrated++;
    }


    printf("Migration actions (CUSTOM {$custom}) : " . $migrated . " actions(s) migrée(s).\n");
}
