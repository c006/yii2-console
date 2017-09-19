<?php

namespace c006\console\controllers;

use yii\db\Connection;
use yii\di\Instance;

class AutoController extends \c006\console\controllers\BaseMigrateController
{
    /**
     * @var string the name of the table for keeping applied migration information.
     */
    public $migrationTable = '{{%migration}}';
    /**
     * @inheritdoc
     */
    public $templateFile = '@yii/views/migration.php';
    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection to use
     * when applying migrations. Starting from version 2.0.3, this can also be a configuration array
     * for creating the object.
     */
    public $db = 'db';



    public function actionEcom() {

        $this->auto_override = TRUE;

        self::actionUp('m000000_000000_c006_url');
        self::actionUp('m000000_000000_c006_user_ecomm');
        self::actionUp('m000000_000000_c006_dev_account');
        self::actionUp('m000000_000000_c006_cart');
        self::actionUp('m000000_000000_c006_checkout');
        self::actionUp('m000000_000000_c006_category');
        self::actionUp('m000000_000000_c006_products');
        self::actionUp('m000000_000000_c006_coupons');
        self::actionUp('m000000_000000_c006_shipping');
        self::actionUp('m000000_000000_c006_email');
        self::actionUp('m000000_000000_c006_common');
    }

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['migrationTable', 'db'] // global for all actions
        );
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * It checks the existence of the [[migrationPath]].
     *
     * @param \yii\base\Action $action the action to be executed.
     *
     * @return boolean whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            if ($action->id !== 'create') {
                $this->db = Instance::ensure($this->db, Connection::className());
            }

            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Creates a new migration instance.
     *
     * @param string $class the migration class name
     *
     * @return \yii\db\Migration the migration instance
     */
    protected function createMigration($class)
    {
        $file = $this->migrationPath . '/' . $class . '.php';

        require_once($file);

        foreach (get_declared_classes() as $k => $v) {
            if (stripos($v, $class) !== FALSE) {
                return new $v(['db' => $this->db]);
            }
        }

        $this->stdout("No migration class found, check the name.\n");

    }


    public function checkAction()
    {

    }

    /**
     * Returns the migration history.
     *
     * @param integer $limit the maximum number of records in the history to be returned. `null` for "no limit".
     *
     * @return array the migration history
     */
    protected function getMigrationHistory($limit)
    {
        // TODO: Implement getMigrationHistory() method.
    }

    /**
     * Adds new migration entry to the history.
     *
     * @param string $version migration version name.
     */
    protected function addMigrationHistory($version)
    {
        // TODO: Implement addMigrationHistory() method.
    }

    /**
     * Removes existing migration from the history.
     *
     * @param string $version migration version name.
     */
    protected function removeMigrationHistory($version)
    {
        // TODO: Implement removeMigrationHistory() method.
    }
}