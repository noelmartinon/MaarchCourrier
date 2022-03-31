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

    $where = ["trim(label_action) = trim( ? )"];

    $element= \Action\models\ActionModel::get([
        'select' => ['*'] ,
        'where' => $where,
        'data' => ['Enregistrer vers le  status : A Valider']
    ]);    


    if($element[0]['id'] > 0 ) {
        //Modification de l'action

        $str['keyword']     = 'redirect';
        $str['action_page'] = 'redirect';
        $str['component']   = 'redirectAction';
        $str['id_status']   = 'VAL';
        \Action\models\ActionModel::update([
            'set' => $str ,
            'where' => ['id = ?'],
            'data' => [$element[0]['id']]
        ]);  

        //Recupération de la bannette
        $groupBaskets = \Basket\models\GroupBasketModel::get([
            'select' => ['*'],
            'where' => ['basket_id = ?'],
            'data' => ['QualificationBasket']
        ]);        

        //Récupération de l'action de la bannette
        $groupActionBaskets = \Basket\models\ActionGroupBasketModel::get([
            'select'    => ['*'],
            'where'     => ['id_action = ?'],
            'data'      => [$element[0]['id']]
        ]);

        if(empty($groupActionBaskets)) {
            \Basket\models\ActionGroupBasketModel::create([
                'id'                => $groupBaskets[0]['basket_id'],
                'groupId'           => $groupBaskets[0]['group_id'],
                'actionId'          => $element[0]['id'],
                'whereClause'       => '',
                'usedInBasketlist'  => 'N',
                'usedInActionPage'  => 'Y',
                'defaultActionList' => 'N'
            ]);

            $groupActionBaskets = \Basket\models\GroupBasketRedirectModel::get([
                'select'    => ['*'],
                'where'     => ['action_id = ?'],
                'data'      => [$element[0]['id']]
            ]);
        }



        $keyword  = 'ALL_ENTITIES';
        $redirect = 'ENTITY';

        if(empty($groupActionBaskets)) {
            
            \Basket\models\GroupBasketRedirectModel::create([
                'groupId'      => $groupBaskets[0]['group_id'],
                'id'     => $groupBaskets[0]['basket_id'],
                'actionId'     => $element[0]['id'],
                'entityId'     => '',
                'keyword'       => $keyword,
                'redirectMode' => $redirect
            ]);
        }else {

            \Basket\models\GroupBasketRedirectModel::update([
                'set' => [
                    'keyword'       => $keyword,
                    'redirect_mode' => $redirect
                ],
                'where' => ['action_id = ?','group_id = ?','basket_id = ?', ],
                'data' => [$element[0]['id'], $groupBasket[0]['group_id'], $groupBasket[0]['basket_id']]
            ]);  
            
        }        
        $bool = true;
    }
    $texte = ($bool == true)? " Mise à jour du paramétrage de l'action ".$element[0]['label_action']." de la bannette ".$groupBasket[0]['basket_id']."\n" : "Modification non effectuée\n";
    printf($texte);

}


