<?php

/**
 * Suppression de l'action 18 Valider un document
 */
require '../../vendor/autoload.php';

chdir('../..');

$migrated = 0;
$customs  = scandir('custom');
$bool = false;

foreach ($customs as $custom) {
    if (in_array($custom, ['custom.json', 'custom.xml', '.', '..'])) {
        continue;
    }

    \SrcCore\models\DatabasePDO::reset();
    new \SrcCore\models\DatabasePDO(['customId' => $custom]);

    $where[] = "trim(label_action) = trim( ? )";

    $element= \Action\models\ActionModel::get([
        'select' => ['id'] ,
        'where' => $where,
        'data' => ['Valider un document']
    ]);    

    if($element[0]['id'] > 0 ) {
        //section du group
        $groupsmodel = \Group\models\GroupModel::get();
        $element = (string)$element[0]['id'];
        $cpt = 0;

        foreach($groupsmodel as $model) {
            $indexation_parameters = json_decode($model['indexation_parameters'], true);
            $result = array_search($element, $indexation_parameters['actions'] );
            if( $result !== false ) {
                unset($indexation_parameters['actions'][array_search($element, $indexation_parameters['actions'])]);
                $cpt += 1;

                $indexation_parameters['actions'] = array_values($indexation_parameters['actions']);

                $str['indexation_parameters'] = json_encode($indexation_parameters);

                \Group\models\GroupModel::update([
                    'set'   => $str,
                    'where' => ['id = ?'],
                    'data'  => [$model['id']]
                ]);
            }            
        }
        
        $bool = true;
    }
    $texte = ($bool == true)? $cpt . " action(s) trouvé(s) et retiré(s)  \n" : "Pas d'action trouvé \n";
    printf($texte);

}


