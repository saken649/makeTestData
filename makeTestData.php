<?php

$MakeTestData = new MakeTestData();
$MakeTestData->main();

class MakeTestData {

    private $planFees = [
        1 => 1000,
        2 => 1500,
        3 => 2500,
        4 => 1700,
        5 => 2200,
        6 => 3200
    ];

    private $paymentYmPattern = [
        ['201701', '201702', '201703', '201704', '201705', '201706', '201707'],
        ['201703', '201704', '201705', '201706', '201707'],
        ['201706', '201707'],
        ['201701', '201702', '201703', '201704', '201705']
    ];

    private $usersSql;
    private $paymentsSql;

    private $pdo;

    const MAX = 500000;
    const UNIT = 1000;

    public function main() {
        try {
            echo "バッチスタート =====>>>>>\r\n";
            $this->_connectPdo();
            for ($i = 0; $i < self::MAX; $i += self::UNIT) {
                $this->_setInitSql();
                for ($j = 1; $j <= self::UNIT; $j++) {
                    $userId = $i + $j;
                    $planId = $this->_getPlan($i);
                    $this->_makeSqlForUser($userId, $planId);
                    $this->_makeSqlForPayments($userId, $planId);
                }
                $this->_execSql();
                echo '-- userId:' . $userId . 'まで完了' . "\r\n";
            }
            echo "<<<<<===== 終了\r\n";
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    private function _connectPdo() {
        try {
            $this->pdo = new PDO('mysql:dbname=explain_test;host=127.0.0.1', 'root', '');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function _setInitSql() {
        $this->usersSql = 'INSERT INTO `users` (`name`, `plan_id`) VALUES ';
        $this->paymentsSql = 'INSERT INTO `payments` (`ym`, `user_id`, `plan_id`, `fee`) VALUES ';
    }

    private function _getPlan($block) {
        $range = range(1, 6);
        shuffle($range);
        return $range[0];
    }

    private function _makeSqlForUser($userId, $planId) {
        $this->usersSql .= sprintf('("test%s", %s),', $userId, $planId);
    }

    private function _makeSqlForPayments($userId, $planId) {
        $fee = $this->_getPlanFee($planId);
        $ymList = $this->_getPaymentYmPattern();
        foreach ($ymList as $ym) {
            $this->paymentsSql .= sprintf('("%s", %s, %s, %s),', $ym, $userId, $planId, $fee);
        }
    }

    private function _getPlanFee($key) {
        return $this->planFees[$key];
    }

    private function _getPaymentYmPattern() {
        $n = range(0, 3);
        shuffle($n);
        return $this->paymentYmPattern[$n[0]];
    }

    private function _execSql() {
        $usersSql = substr($this->usersSql, 0, -1);
        $paymentsSql = substr($this->paymentsSql, 0, -1);
        try {
            $this->pdo->beginTransaction();
            if (!$this->pdo->query($usersSql)) {
                throw new Exception('usersSqlの失敗: ' . $usersSql . "\r\n");
            }
            if (!$this->pdo->query($paymentsSql)) {
                throw new Exception('paymentsSqlの失敗: ' . $paymentsSql . "\r\n");
            }
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
}
