<?php
class IndexController extends Yaf_Controller_Abstract {
    public function indexAction() {

        /**
         * 例一:示例多数据库连接
         */
        $mymodel = new Db_Dao('user_finance', 'mydb');
        $model = new Db_Dao('goods_order');
        $data1 = $model->query('*', array('uid' => 1));
        $data2 = $mymodel->query('*', array('uid' => 2));
        //exit(json_encode($data1));

        /**
         * 例二:示例简单查询,示例中的查询结果相同
         */
        $data3 = $model->query('uid, gid', array('uid' => 1));
        $data4 = $model->query(array('uid', 'gid'), array('uid' => 1));
        $data5 = $model->query(array('uid', 'gid'), array('uid = 1' => array()));
        $data5 = $model->query(array('uid', 'gid'), array('uid = :uid' => array(':uid' => 1)));
        //exit(json_encode($data5));

        /**
         * 例三:示例简单查询,示例中的查询结果相同
         */
        $data6 = $model->query('*', array('uid' => 1, 'gid' => 3));
        $data7 = $model->query(array('*'), array('uid' => 1, 'gid' => 3));
        $data8 = $model->query(array('*'), array('uid = 1 and gid = 3' => array()));
        $data9 = $model->query(array('*'), array('uid = :uid and gid = :gid' => array(':uid' => 1, ':gid' => 3)));
        //exit(json_encode($data9));

        /**
         * 例三:示例简单查询,示例limit + offset与order by
         */
        $data10 = $model->query('*', array());
        $data11 = $model->query('*', array(), 2);
        $data12 = $model->query('*', array(), 2, 1);
        $data13 = $model->query('*', array(), 5, 0, 'uid desc, gid desc');
        $data14 = $model->query('*', array(), 5, 0, array('uid desc', 'gid desc'));
        //exit(json_encode($data13));

        /**
         * 例四:示例简单查询,示例group by与having(having只能使用string)
         */
        $data15 = $model->query('count(*)', array());
        $data16 = $model->query('uid, gid, count(*)', array(), false, 0, false, 'uid,gid');
        $data17 = $model->query('uid, gid, count(*)', array(), false, 0, false, array('uid', 'gid'));
        $data18 = $model->query('uid, gid, count(*) as cnt', array(), false, 0, false, array('uid', 'gid'), 'count(*) >= 20');
        //exit(json_encode($data18));

        /**
         * 例五:示例插入
         */
        //$model->insert(array('uid' => 3, 'gid' => 5));

        /**
         * 例六:示例更新
         */
        //$result = $model->update(array('gtid' => rand(1, 100)), array('uid' => 3, 'gid' => 5));
        //exit(json_encode(array($result)));

        /**
         * 例七:示例事务
         */
        //$trans = $model->beginTransaction();
        //$result = $model->update(array('gtid' => rand(500, 1000)), array('uid' => 3, 'gid' => 5));
        //$trans->commit();
        //$trans->rollback();
        //exit(json_encode(array($result)));

        /**
         * 例八:yii command写法
         */
        $command = $model->getConnection()->createCommand();
        $result = $command->select(array('uid'))->from($model->getTableName())->where('uid < :uid', array(':uid' => 2))->queryAll();
        //exit(json_encode(array($result)));

        /**
         * 例九:sql
         */
        $sql = 'select * from user_finance';
        $command = $model->getConnection()->createCommand($sql);
        $result = $command->queryAll();
        //exit(json_encode(array($result)));

        /**
         * 例十:特殊查询方式:OR,参数2与参数3只支持string.
         */
        $result = $model->query('*', array(array('OR', 'uid < 2', 'uid > 2')));
        //exit(json_encode($result));

        /**
         * 例十:特殊查询方式:IN/NOT以及与其它条件的联用方式
         */
        $result1 = $model->query('*', array(array('IN', 'uid', array(1,2))));
        $result2 = $model->query('*', array(array('NOT IN', 'uid', array(1,2))));
        $result3 = $model->query('*', array(array('IN', 'uid', array(1,2)), 'gid != :gid' => array(':gid' => 5), 'gtid' => 5));
        //exit(json_encode($result3));

        /**
         * 其它feature:获取上一次查询的sql语句.出于性能考虑,生产环境可以把此feature下线.
         */
        $result4 = $model->query('*', array(array('IN', 'uid', array(1,2)), 'gid != :gid' => array(':gid' => 5), 'gtid' => 5), 4, 1, 'gtid desc');
        exit($model->getText());

        /**
         * 其它说明:没有另外实现join,union等功能,可以直接使用yii原生的command.
         * 由于join很难避免全表查询,表大的时候很可能把数据库卡死,所以宁愿多查询几次,也尽量不要使用join.
         */
    }
}
?>