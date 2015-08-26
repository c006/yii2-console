<?php

namespace c006\console\controllers;

use c006\core\assets\CoreHelper;
use Yii;
use yii\console\Controller;
use yii\console\Exception;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * BaseMigrateController is base class for migrate controllers.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since  2.0
 */
abstract class BaseMigrateController extends Controller
{
    /**
     * The name of the dummy migration that marks the beginning of the whole migration history.
     */
    const BASE_MIGRATION = 'm000000_000000_base';

    /**
     * @var string the default command action.
     */
    public $defaultAction = 'up';
    /**
     * @var string the directory storing the migration classes. This can be either
     * a path alias or a directory.
     */
    public $migrationPath = '@app/migrations';
    /**
     * @var string the template file for generating new migrations.
     * This can be either a path alias (e.g. "@app/migrations/template.php")
     * or a file path.
     */
    public $templateFile;


    function init()
    {

    }

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['m'], // global for all actions
            ($actionID == 'create') ? ['templateFile'] : [] // action create
        );
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * It checks the existence of the [[migrationPath]].
     *
     * @param \yii\base\Action $action the action to be executed.
     *
     * @throws Exception if directory specified in migrationPath doesn't exist and action isn't "create".
     * @return boolean whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            $path = Yii::getAlias($this->migrationPath);
            if (!is_dir($path)) {
                if ($action->id !== 'create') {
                    throw new Exception("Migration failed. Directory specified in migrationPath doesn't exist: {$this->migrationPath}");
                }
                FileHelper::createDirectory($path);
            }
            $this->migrationPath = $path;

            $version = Yii::getVersion();
            $this->stdout("Yii Migration Tool (based on Yii v{$version})\n\n");

            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * @param string $migration
     *
     * @return int
     */
    public function actionUp($migration = '')
    {

        if (empty($migration)) {
            $this->stdout("No new migration found. Your system is up-to-date.\n", Console::FG_GREEN);

            return self::EXIT_CODE_NORMAL;
        }

        /* ~ C006 */
        $base = str_replace('\console', '', Yii::$app->basePath) . '/vendor';

        $array = CoreHelper::recursiveDirectory($base, $base);

        foreach ($array as $vendor) {
            $vendor = $vendor['item'];
            if ($vendor['is_dir'] && isset($vendor['sub_folders']) && sizeof($vendor['sub_folders'])) {
                //$this->stdout($vendor['folder'] . "\n", Console::FG_RED);
                foreach ($vendor['sub_folders'] as $extension) {
                    $extension = $extension['item'];
                    if ($extension['is_dir'] && isset($extension['sub_folders']) && sizeof($extension['sub_folders'])) {
                        foreach ($extension['sub_folders'] as $folder) {
                            $folder = $folder['item'];
                            if ($folder['is_dir'] && $folder['folder'] == "migrations") {
                                if (isset($folder['sub_folders']) && sizeof($folder['sub_folders'])) {
                                    foreach ($folder['sub_folders'] as $file) {
                                        $file = $file['item'];
                                        if ($file['file'] == $migration . '.php') {
                                            //die($file['relative'] .'/'. $file['file'] ."\n\n");
                                            $this->migrationPath = $file['path'];
                                        }
                                    }
                                }
                            }
                        }
                    }

                }
            }
        }

        //Yii::warning(print_r($array, TRUE), '');
//        exit;

        $this->stdout("\t$migration\n");
        $this->stdout("\n");

        if ($this->confirm("Run: ", FALSE)) {
            if (!$this->migrateUp($migration)) {
                $this->stdout("\nMigration failed.\n", Console::FG_RED);

                return self::EXIT_CODE_ERROR;
            }
            $this->stdout("\nMigrated up successfully.\n", Console::FG_GREEN);
        } else {
            $this->stdout("\nBye.\n", Console::FG_GREEN);
        }
    }

    /**
     * Downgrades the application by reverting old migrations.
     * For example,
     *
     * ~~~
     * yii migrate/down     # revert the last migration
     * yii migrate/down 3   # revert the last 3 migrations
     * yii migrate/down all # revert all migrations
     * ~~~
     *
     * @param integer $limit the number of migrations to be reverted. Defaults to 1,
     *                       meaning the last applied migration will be reverted.
     *
     * @throws Exception if the number of the steps specified is less than 1.
     *
     * @return integer the status of the action execution. 0 means normal, other values mean abnormal.
     */
    public function actionDown($limit = 1)
    {
        if ($limit === 'all') {
            $limit = NULL;
        } else {
            $limit = (int)$limit;
            if ($limit < 1) {
                throw new Exception("The step argument must be greater than 0.");
            }
        }

        $migrations = $this->getMigrationHistory($limit);

        if (empty($migrations)) {
            $this->stdout("No migration has been done before.\n", Console::FG_YELLOW);

            return self::EXIT_CODE_NORMAL;
        }

        $migrations = array_keys($migrations);

        $n = count($migrations);
        $this->stdout("Total $n " . ($n === 1 ? 'migration' : 'migrations') . " to be reverted:\n", Console::FG_YELLOW);
        foreach ($migrations as $migration) {
            $this->stdout("\t$migration\n");
        }
        $this->stdout("\n");

        if ($this->confirm('Revert the above ' . ($n === 1 ? 'migration' : 'migrations') . "?")) {
            foreach ($migrations as $migration) {
                if (!$this->migrateDown($migration)) {
                    $this->stdout("\nMigration failed. The rest of the migrations are canceled.\n", Console::FG_RED);

                    return self::EXIT_CODE_ERROR;
                }
            }
            $this->stdout("\nMigrated down successfully.\n", Console::FG_GREEN);
        }
    }

    /**
     * Redoes the last few migrations.
     *
     * This command will first revert the specified migrations, and then apply
     * them again. For example,
     *
     * ~~~
     * yii migrate/redo     # redo the last applied migration
     * yii migrate/redo 3   # redo the last 3 applied migrations
     * yii migrate/redo all # redo all migrations
     * ~~~
     *
     * @param integer $limit the number of migrations to be redone. Defaults to 1,
     *                       meaning the last applied migration will be redone.
     *
     * @throws Exception if the number of the steps specified is less than 1.
     *
     * @return integer the status of the action execution. 0 means normal, other values mean abnormal.
     */
    public function actionRedo($limit = 1)
    {
        if ($limit === 'all') {
            $limit = NULL;
        } else {
            $limit = (int)$limit;
            if ($limit < 1) {
                throw new Exception("The step argument must be greater than 0.");
            }
        }

        $migrations = $this->getMigrationHistory($limit);

        if (empty($migrations)) {
            $this->stdout("No migration has been done before.\n", Console::FG_YELLOW);

            return self::EXIT_CODE_NORMAL;
        }

        $migrations = array_keys($migrations);

        $n = count($migrations);
        $this->stdout("Total $n " . ($n === 1 ? 'migration' : 'migrations') . " to be redone:\n", Console::FG_YELLOW);
        foreach ($migrations as $migration) {
            $this->stdout("\t$migration\n");
        }
        $this->stdout("\n");

        if ($this->confirm('Redo the above ' . ($n === 1 ? 'migration' : 'migrations') . "?")) {
            foreach ($migrations as $migration) {
                if (!$this->migrateDown($migration)) {
                    $this->stdout("\nMigration failed. The rest of the migrations are canceled.\n", Console::FG_RED);

                    return self::EXIT_CODE_ERROR;
                }
            }
            foreach (array_reverse($migrations) as $migration) {
                if (!$this->migrateUp($migration)) {
                    $this->stdout("\nMigration failed. The rest of the migrations migrations are canceled.\n", Console::FG_RED);

                    return self::EXIT_CODE_ERROR;
                }
            }
            $this->stdout("\nMigration redone successfully.\n", Console::FG_GREEN);
        }
    }

    /**
     * Upgrades or downgrades till the specified version.
     *
     * Can also downgrade versions to the certain apply time in the past by providing
     * a UNIX timestamp or a string parseable by the strtotime() function. This means
     * that all the versions applied after the specified certain time would be reverted.
     *
     * This command will first revert the specified migrations, and then apply
     * them again. For example,
     *
     * ~~~
     * yii migrate/to 101129_185401                      # using timestamp
     * yii migrate/to m101129_185401_create_user_table   # using full name
     * yii migrate/to 1392853618                         # using UNIX timestamp
     * yii migrate/to "2014-02-15 13:00:50"              # using strtotime() parseable string
     * ~~~
     *
     * @param string $version either the version name or the certain time value in the past
     *                        that the application should be migrated to. This can be either the timestamp,
     *                        the full name of the migration, the UNIX timestamp, or the parseable datetime
     *                        string.
     *
     * @throws Exception if the version argument is invalid.
     */
    public function actionTo($version)
    {
        if (preg_match('/^m?(\d{6}_\d{6})(_.*?)?$/', $version, $matches)) {
            $this->migrateToVersion('m' . $matches[1]);
        } elseif ((string)(int)$version == $version) {
            $this->migrateToTime($version);
        } elseif (($time = strtotime($version)) !== FALSE) {
            $this->migrateToTime($time);
        } else {
            throw new Exception("The version argument must be either a timestamp (e.g. 101129_185401),\n the full name of a migration (e.g. m101129_185401_create_user_table),\n a UNIX timestamp (e.g. 1392853000), or a datetime string parseable\nby the strtotime() function (e.g. 2014-02-15 13:00:50).");
        }
    }

    /**
     * Modifies the migration history to the specified version.
     *
     * No actual migration will be performed.
     *
     * ~~~
     * yii migrate/mark 101129_185401                      # using timestamp
     * yii migrate/mark m101129_185401_create_user_table   # using full name
     * ~~~
     *
     * @param string $version the version at which the migration history should be marked.
     *                        This can be either the timestamp or the full name of the migration.
     *
     * @return integer CLI exit code
     * @throws Exception if the version argument is invalid or the version cannot be found.
     */
    public function actionMark($version)
    {
        $originalVersion = $version;
        if (preg_match('/^m?(\d{6}_\d{6})(_.*?)?$/', $version, $matches)) {
            $version = 'm' . $matches[1];
        } else {
            throw new Exception("The version argument must be either a timestamp (e.g. 101129_185401)\nor the full name of a migration (e.g. m101129_185401_create_user_table).");
        }

        // try mark up
        $migrations = $this->getNewMigrations();
        foreach ($migrations as $i => $migration) {
            if (strpos($migration, $version . '_') === 0) {
                if ($this->confirm("Set migration history at $originalVersion?")) {
                    for ($j = 0; $j <= $i; ++$j) {
                        $this->addMigrationHistory($migrations[ $j ]);
                    }
                    $this->stdout("The migration history is set at $originalVersion.\nNo actual migration was performed.\n", Console::FG_GREEN);
                }

                return self::EXIT_CODE_NORMAL;
            }
        }

        // try mark down
        $migrations = array_keys($this->getMigrationHistory(NULL));
        foreach ($migrations as $i => $migration) {
            if (strpos($migration, $version . '_') === 0) {
                if ($i === 0) {
                    $this->stdout("Already at '$originalVersion'. Nothing needs to be done.\n", Console::FG_YELLOW);
                } else {
                    if ($this->confirm("Set migration history at $originalVersion?")) {
                        for ($j = 0; $j < $i; ++$j) {
                            $this->removeMigrationHistory($migrations[ $j ]);
                        }
                        $this->stdout("The migration history is set at $originalVersion.\nNo actual migration was performed.\n", Console::FG_GREEN);
                    }
                }

                return self::EXIT_CODE_NORMAL;
            }
        }

        throw new Exception("Unable to find the version '$originalVersion'.");
    }

    /**
     * Displays the migration history.
     *
     * This command will show the list of migrations that have been applied
     * so far. For example,
     *
     * ~~~
     * yii migrate/history     # showing the last 10 migrations
     * yii migrate/history 5   # showing the last 5 migrations
     * yii migrate/history all # showing the whole history
     * ~~~
     *
     * @param integer $limit the maximum number of migrations to be displayed.
     *                       If it is "all", the whole migration history will be displayed.
     *
     * @throws \yii\console\Exception if invalid limit value passed
     */
    public function actionHistory($limit = 10)
    {
        if ($limit === 'all') {
            $limit = NULL;
        } else {
            $limit = (int)$limit;
            if ($limit < 1) {
                throw new Exception("The limit must be greater than 0.");
            }
        }

        $migrations = $this->getMigrationHistory($limit);

        if (empty($migrations)) {
            $this->stdout("No migration has been done before.\n", Console::FG_YELLOW);
        } else {
            $n = count($migrations);
            if ($limit > 0) {
                $this->stdout("Showing the last $n applied " . ($n === 1 ? 'migration' : 'migrations') . ":\n", Console::FG_YELLOW);
            } else {
                $this->stdout("Total $n " . ($n === 1 ? 'migration has' : 'migrations have') . " been applied before:\n", Console::FG_YELLOW);
            }
            foreach ($migrations as $version => $time) {
                $this->stdout("\t(" . date('Y-m-d H:i:s', $time) . ') ' . $version . "\n");
            }
        }
    }

    /**
     * Displays the un-applied new migrations.
     *
     * This command will show the new migrations that have not been applied.
     * For example,
     *
     * ~~~
     * yii migrate/new     # showing the first 10 new migrations
     * yii migrate/new 5   # showing the first 5 new migrations
     * yii migrate/new all # showing all new migrations
     * ~~~
     *
     * @param integer $limit the maximum number of new migrations to be displayed.
     *                       If it is `all`, all available new migrations will be displayed.
     *
     * @throws \yii\console\Exception if invalid limit value passed
     */
    public function actionNew($limit = 10)
    {
        if ($limit === 'all') {
            $limit = NULL;
        } else {
            $limit = (int)$limit;
            if ($limit < 1) {
                throw new Exception("The limit must be greater than 0.");
            }
        }

        $migrations = $this->getNewMigrations();

        if (empty($migrations)) {
            $this->stdout("No new migrations found. Your system is up-to-date.\n", Console::FG_GREEN);
        } else {
            $n = count($migrations);
            if ($limit && $n > $limit) {
                $migrations = array_slice($migrations, 0, $limit);
                $this->stdout("Showing $limit out of $n new " . ($n === 1 ? 'migration' : 'migrations') . ":\n", Console::FG_YELLOW);
            } else {
                $this->stdout("Found $n new " . ($n === 1 ? 'migration' : 'migrations') . ":\n", Console::FG_YELLOW);
            }

            foreach ($migrations as $migration) {
                $this->stdout("\t" . $migration . "\n");
            }
        }
    }

    /**
     * Creates a new migration.
     *
     * This command creates a new migration using the available migration template.
     * After using this command, developers should modify the created migration
     * skeleton by filling up the actual migration logic.
     *
     * ~~~
     * yii migrate/create create_user_table
     * ~~~
     *
     * @param string $name the name of the new migration. This should only contain
     *                     letters, digits and/or underscores.
     *
     * @throws Exception if the name argument is invalid.
     */
    public function actionCreate($name)
    {
        if (!preg_match('/^\w+$/', $name)) {
            throw new Exception("The migration name should contain letters, digits and/or underscore characters only.");
        }

        $name = 'm' . gmdate('ymd_His') . '_' . $name;
        $file = $this->migrationPath . DIRECTORY_SEPARATOR . $name . '.php';

        if ($this->confirm("Create new migration '$file'?")) {
            $content = $this->renderFile(Yii::getAlias($this->templateFile), ['className' => $name]);
            file_put_contents($file, $content);
            $this->stdout("New migration created successfully.\n", Console::FG_GREEN);
        }
    }

    /**
     * Upgrades with the specified migration class.
     *
     * @param string $class the migration class name
     *
     * @return boolean whether the migration is successful
     */
    protected function migrateUp($class)
    {
        if ($class === self::BASE_MIGRATION) {
            return TRUE;
        }

        $this->stdout("*** applying $class\n", Console::FG_YELLOW);
        $start = microtime(TRUE);
        $migration = $this->createMigration($class);
        if ($migration->up() !== FALSE) {
            $this->addMigrationHistory($class);
            $time = microtime(TRUE) - $start;
            $this->stdout("*** applied $class (time: " . sprintf("%.3f", $time) . "s)\n\n", Console::FG_GREEN);

            return TRUE;
        } else {
            $time = microtime(TRUE) - $start;
            $this->stdout("*** failed to apply $class (time: " . sprintf("%.3f", $time) . "s)\n\n", Console::FG_RED);

            return FALSE;
        }
    }

    /**
     * Downgrades with the specified migration class.
     *
     * @param string $class the migration class name
     *
     * @return boolean whether the migration is successful
     */
    protected function migrateDown($class)
    {
        if ($class === self::BASE_MIGRATION) {
            return TRUE;
        }

        $this->stdout("*** reverting $class\n", Console::FG_YELLOW);
        $start = microtime(TRUE);
        $migration = $this->createMigration($class);
        if ($migration->down() !== FALSE) {
            $this->removeMigrationHistory($class);
            $time = microtime(TRUE) - $start;
            $this->stdout("*** reverted $class (time: " . sprintf("%.3f", $time) . "s)\n\n", Console::FG_GREEN);

            return TRUE;
        } else {
            $time = microtime(TRUE) - $start;
            $this->stdout("*** failed to revert $class (time: " . sprintf("%.3f", $time) . "s)\n\n", Console::FG_RED);

            return FALSE;
        }
    }

    /**
     * Creates a new migration instance.
     *
     * @param string $class the migration class name
     *
     * @return \yii\db\MigrationInterface the migration instance
     */
    protected function createMigration($class)
    {
        $file = $this->migrationPath . DIRECTORY_SEPARATOR . $class . '.php';
        require_once($file);

        return new $class();
    }

    /**
     * Migrates to the specified apply time in the past.
     *
     * @param integer $time UNIX timestamp value.
     */
    protected function migrateToTime($time)
    {
        $count = 0;
        $migrations = array_values($this->getMigrationHistory(NULL));
        while ($count < count($migrations) && $migrations[ $count ] > $time) {
            ++$count;
        }
        if ($count === 0) {
            $this->stdout("Nothing needs to be done.\n", Console::FG_GREEN);
        } else {
            $this->actionDown($count);
        }
    }

    /**
     * Migrates to the certain version.
     *
     * @param string $version name in the full format.
     *
     * @return integer CLI exit code
     * @throws Exception if the provided version cannot be found.
     */
    protected function migrateToVersion($version)
    {
        $originalVersion = $version;

        // try migrate up
        $migrations = $this->getNewMigrations();
        foreach ($migrations as $i => $migration) {
            if (strpos($migration, $version . '_') === 0) {
                $this->actionUp($i + 1);

                return self::EXIT_CODE_NORMAL;
            }
        }

        // try migrate down
        $migrations = array_keys($this->getMigrationHistory(NULL));
        foreach ($migrations as $i => $migration) {
            if (strpos($migration, $version . '_') === 0) {
                if ($i === 0) {
                    $this->stdout("Already at '$originalVersion'. Nothing needs to be done.\n", Console::FG_YELLOW);
                } else {
                    $this->actionDown($i);
                }

                return self::EXIT_CODE_NORMAL;
            }
        }

        throw new Exception("Unable to find the version '$originalVersion'.");
    }

    /**
     * Returns the migrations that are not applied.
     * @return array list of new migrations
     */
    protected function getNewMigrations()
    {
        $applied = [];
        foreach ($this->getMigrationHistory(NULL) as $version => $time) {
            $applied[ substr($version, 1, 13) ] = TRUE;
        }

        $migrations = [];
        $handle = opendir($this->migrationPath);
        while (($file = readdir($handle)) !== FALSE) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $this->migrationPath . DIRECTORY_SEPARATOR . $file;
            if (preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) && !isset($applied[ $matches[2] ]) && is_file($path)) {
                $migrations[] = $matches[1];
            }
        }
        closedir($handle);
        sort($migrations);

        return $migrations;
    }

    /**
     * Returns the migration history.
     *
     * @param integer $limit the maximum number of records in the history to be returned. `null` for "no limit".
     *
     * @return array the migration history
     */
    abstract protected function getMigrationHistory($limit);

    /**
     * Adds new migration entry to the history.
     *
     * @param string $version migration version name.
     */
    abstract protected function addMigrationHistory($version);

    /**
     * Removes existing migration from the history.
     *
     * @param string $version migration version name.
     */
    abstract protected function removeMigrationHistory($version);
}