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

    // recupere la liste des usergroups
    $elements = \Group\models\GroupModel::get([

    ]);    

    //recupere l'id de l'action indexer un document
    $action21= \Action\models\ActionModel::get([
        'select' => ['id'] ,
        'where' => ['trim(label_action) = trim( ? )'],
        'data' => ['Indexer un document']
    ]); 
    $cpt = 0;
    $found21=0;

    foreach($elements as $element) {
        $indexation_parameters = json_decode($element['indexation_parameters'], true);
        
        if(count($indexation_parameters['actions']) <= 0 ) {
            //next 
            continue;
        }

        $actions = $indexation_parameters['actions'] ;

        $position = array_search($action21[0]['id'], $indexation_parameters['actions'] );
        if( $position !== false ) {
            // on recuper la position du num 21 
            $position = $position;
            $found21 += 1;
            if($position > 0) {
                // puis on echange avec le premier
                $tempContent = $actions[0];
                $actions[0] = (string)$action21[0]['id'];
                $actions[$position] = $tempContent;

                $cpt += 1;
                $bool = true;

                $indexation_parameters['actions'] = array_values($actions);

                $str['indexation_parameters'] = json_encode($indexation_parameters);

                \Group\models\GroupModel::update([
                    'set'   => $str,
                    'where' => ['id = ?'],
                    'data'  => [$element['id']]
                ]);
            }
        }        
    }
        
    $texte = ($bool == true)? $found21 . " action(s) trouvé(s) \n" . $cpt . " action(s) ont été mise par défaut \n" . ($found21-$cpt) . " action(s) sont déjà par défaut \n" : "Pas d'action trouvé \n";
    printf($texte);

}

