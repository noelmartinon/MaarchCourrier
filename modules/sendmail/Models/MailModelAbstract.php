<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Mail Model
* @author dev@maarch.org
* @ingroup sendmail
*/


require_once 'apps/maarch_entreprise/services/Table.php';

class MailModelAbstract extends Apps_Table_Service
{
    private $db;

    public function __construct($db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = new Database();
        }
    }

    public function CreateMail($data)
    {
        try {
            $query = ("INSERT INTO sendmail (
                coll_id,
				res_id,
				user_id,
				to_list,
				cc_list,
				cci_list,
				email_object,
	            email_body ,
				is_res_master_attached,
				email_status,
				creation_date,
				sender_email,
				message_exchange_id)
				VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");

            $queryParams[] = $data->coll_id;
            $queryParams[] = $data->res_id;
            $queryParams[] = $data->user_id;
            $queryParams[] = $data->to_list;
            $queryParams[] = $data->cc_list;
            $queryParams[] = $data->cci_list;
            $queryParams[] = $data->email_object;
            $queryParams[] = $data->email_body;
            $queryParams[] = $data->is_res_master_attached;
            $queryParams[] = $data->email_status;
            $queryParams[] = $data->creation_date;
            $queryParams[] = $data->sender_email;
            $queryParams[] = $data->message_exchange_id;

            $res = $this->db->query($query,$queryParams);

        } catch (Exception $e) {
            return false;
        }
    }
}
