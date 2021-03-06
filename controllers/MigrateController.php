<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace c006\console\controllers;

use Yii;
use yii\db\Connection;
use yii\di\Instance;

/**
 * Manages application migrations.
 *
 * A migration means a set of persistent changes to the application environment
 * that is shared among different developers. For example, in an application
 * backed by a database, a migration may refer to a set of changes to
 * the database, such as creating a new table, adding a new table column.
 *
 * This command provides support for tracking the migration history, upgrading
 * or downloading with migrations, and creating new migration skeletons.
 *
 * The migration history is stored in a database table named
 * as [[migrationTable]]. The table will be automatically created the first time
 * this command is executed, if it does not exist. You may also manually
 * create it as follows:
 *
 * ~~~
 * CREATE TABLE migration (
 *     version varchar(180) PRIMARY KEY,
 *     apply_time integer
 * )
 * ~~~
 *
 * Below are some common usages of this command:
 *
 * ~~~
 * # creates a new migration named 'create_user_table'
 * yii migrate/create create_user_table
 *
 * # applies ALL new migrations
 * yii migrate
 *
 * # reverts the last applied migration
 * yii migrate/down
 * ~~~
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since  2.0
 */
class MigrateController extends \c006\console\controllers\BaseMigrateController
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
