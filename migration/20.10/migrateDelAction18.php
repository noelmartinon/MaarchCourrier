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
            $indexation_paramters = json_decode($model['indexation_parameters'], true);
            $result = array_search($element, $indexation_paramters['actions'] );
            if( $result !== false ) {
                unset($indexation_paramters['actions'][array_search($element, $indexation_paramters['actions'])]);
                $cpt += 1;
            }

            $actions  = concate($indexation_paramters['actions']);
            $entities = concate($indexation_paramters['entities']);
            $keywords = concate($indexation_paramters['keywords']);

            $str = '{"actions":['.$actions.'], "entities":['.$entities.'],"keywords":['.$keywords.']}';

            \Group\models\GroupModel::update([
                'set'   => ['indexation_parameters' => $str ],
                'where' => ['id = ?'],
                'data'  => [$model['id']]
            ]);
        }
        
        \Action\models\ActionModel::delete([
           'id' => $element
        ]);
        $bool = true;
    }

    $texte = ($bool == true)? $cpt . " action(s) trouvé(s) \n" : "Pas d'action trouvé \n";
    printf($texte);

}

function concate($elements) {
    $numItems = count($elements);
    $i = 0;
    foreach($elements as $element) {
        $string .= '"'.$element.'"';
        if(++$i < $numItems) {
            $string .= ", ";
        }
    }

    return $string;
}


