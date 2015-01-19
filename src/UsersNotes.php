<?php

namespace CS\Users;

use \PDO,
    \Exception;

class UsersNotes {

    const TYPE_SYSTEM = 'sys';
    const TYPE_AUTH  = 'auth';

    protected $availableTypes = array(
        self::TYPE_AUTH,
        self::TYPE_SYSTEM,
    );

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function addSystemNote($userId, $type = self::TYPE_SYSTEM, $adminId = null, $joinId = null, $content = '')
    {
        switch(true){
            case !in_array($type, $this->availableTypes):
                throw new WrongSystemNoteType;

            case $type == self::TYPE_SYSTEM && !is_null($joinId):
            case $type != self::TYPE_AUTH && (int)$joinId:
                throw new WrongSystemNoteParams;

        }

        $query = $this->db->prepare("
            INSERT INTO users_system_notes
            SET user_id = :user_id,
                admin_id = :admin_id,
                `type` = :type,
                join_id = :join_id,
                content = :message");
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':type', $type, PDO::PARAM_STR);
        $query->bindParam(':admin_id', $adminId, $adminId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $query->bindParam(':join_id', $joinId, $joinId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $query->bindParam(':message', $content, PDO::PARAM_STR);

        return $query->execute();
    }

    public function getSystemNotes($userId, $params = array()) {
        $limit = "";
        if (isset($params['iDisplayStart'])) {
            $limit = "LIMIT " . intval($params['iDisplayStart']) . ", " . intval($params['iDisplayLength']);
        }

        $userId = (int)$userId;
        $records = $this->db->query("
            select SQL_CALC_FOUND_ROWS 
                #*, p.type payment_type,
                
                l.date date,
                admin.email actor,
                l.type type,
                l.content description,
                
                auth.ip, auth.mobile, auth.tablet, auth.browser, auth.browser_version, auth.platform, auth.platform_version
            
            from users_system_notes l
            
            left join admin_users admin on l.admin_id is not null and admin.id = l.admin_id
            
            left join users_auth_log auth on l.`type` = 'auth' and l.join_id = auth.id and l.user_id = auth.user_id
            
            #left join orders o on l.`type` = 'pay' and l.user_id = o.user_id
            #left join orders_payments p on l.`type` = 'pay' and l.join_id = p.id and p.order_id = o.id
            #left join orders_payments_products pp on pp.order_payment_id = p.id
            #left join orders_products op on pp.order_product_id = op.id
            #left join products pr on op.product_id = pr.id
            
            where l.user_id = {$userId}
            group by l.id
            order by date desc  " . $limit)->fetchAll(PDO::FETCH_ASSOC);

        $total = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        return array(
            "sEcho" => intval($params['sEcho']),
            "iTotalRecords" => $total,
            "iTotalDisplayRecords" => $total,
            "aaData" => $records
        );
    }


}

class WrongSystemNoteType extends Exception {}

class WrongSystemNoteParams extends Exception {}