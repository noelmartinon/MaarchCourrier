<?php

/*
 *    Copyright 2015 Maarch
 *
 *  This file is part of Maarch Framework.
 *
 *   Maarch Framework is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Maarch Framework is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *    along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'apps/maarch_entreprise/services/Table.php';

class NotesModelAbstract extends Apps_Table_Service 
{

    public static function getByResId(array $aArgs = []) 
    {
        static::checkRequired($aArgs, ['resId']);
        static::checkNumeric($aArgs, ['resId']);

        //get notes
        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['notes', 'users', 'users_entities'],
            'left_join' => ['notes.user_id = users.user_id', 'users.user_id = users_entities.user_id'],
            'where'     => ['notes.identifier = ?', 'users_entities.primary_entity=\'Y\''],
            'data'      => [$aArgs['resId']],
            'order_by'  => empty($aArgs['orderBy']) ? ['date_note'] : $aArgs['orderBy']
        ]);

        $tmpNoteId = [];
        foreach ($aReturn as $value) {
            $tmpNoteId[] = $value['id'];
        }
        //get entities

        if (!empty($tmpNoteId)) {
            $tmpEntitiesRestriction = [];
            $entities = static::select([
                'select'   => ['note_id', 'item_id'],
                'table'    => ['note_entities'],
                'where'    => ['note_id in (?)'],
                'data'     => [$tmpNoteId],
                'order_by' => ['item_id']
            ]);

            foreach ($entities as $key => $value) {
                $tmpEntitiesRestriction[$value['note_id']][] = $value['item_id'];
            }
        }

        foreach ($aReturn as $key => $value) {
            if (!empty($tmpEntitiesRestriction[$value['id']])) {
                $aReturn[$key]['entities_restriction'] = implode(", ", $tmpEntitiesRestriction[$value['id']]);
            }
        }

        return $aReturn;
    }

    public static function countForCurrentUserByResId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['resId']);
        static::checkNumeric($aArgs, ['resId']);

        $nb = 0;
        $countedNotes = [];
        $entities = [];

        $aEntities = static::select([
            'select' => 'entity_id',
            'table'  => ['users_entities'],
            'where'  => ['user_id = ?'],
            'data'   => [$_SESSION['user']['UserId']]
        ]);

        foreach ($aEntities as $value) {
            $entities[] = $value['entity_id'];
        }

        $aNotes = static::select([
            'select'    => ['notes.id','user_id', 'item_id'],
            'table'     => ['notes', 'note_entities'],
            'left_join' => ['notes.id = note_entities.note_id'],
            'where'     => ['identifier = ?'],
            'data'      => [$aArgs['resId']]
        ]);

        foreach ($aNotes as $value) {
            if (empty($value['item_id']) && !in_array($value['id'], $countedNotes)) {
                ++$nb;
                $countedNotes[] = $value['id'];
            } elseif (!empty($value['item_id'])) {
                if ($value['user_id'] == $_SESSION['user']['UserId'] && !in_array($value['id'], $countedNotes)) {
                    ++$nb;
                    $countedNotes[] = $value['id'];
                } elseif (in_array($value['item_id'], $entities) && !in_array($value['id'], $countedNotes)) {
                    ++$nb;
                    $countedNotes[] = $value['id'];
                }
            }
        }

        return $nb;
    }

    public static function create(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['identifier', 'tablename', 'user_id', 'coll_id', 'note_text']);
        static::checkNumeric($aArgs, ['identifier']);

        static::insertInto([
            'identifier' => $aArgs['identifier'],
            'tablename'  => $aArgs['tablename'],
            'user_id'    => $aArgs['user_id'],
            'date_note'  => 'CURRENT_TIMESTAMP',
            'note_text'  => $aArgs['note_text'],
            'coll_id'    => $aArgs['coll_id'],
        ], 'notes');

        return true;
    }

}
