<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Dump the database to a file for this application.
 * @category Database
 */
class Command_Dump extends Command_Base {
	protected array $load_modules = [
		'database',
	];

	protected array $option_types = [
		'name' => 'string',
		'echo' => 'boolean',
		'file' => 'boolean',
		'target' => 'string',
		'compress' => 'boolean',
		'no-compress' => 'boolean',
		'non-blocking' => 'boolean',
		'dir' => 'string',
		'arg-prefix' => 'string',
		'arg-suffix' => 'string',
		'tables' => 'list',
		'*' => 'string',
	];

	protected array $option_help = [
		'name' => 'Database name to dump',
		'echo' => 'Output the shell command to do the dump instead of running it',
		'file' => 'Presence means write the output to a file instead of stdout',
		'target' => 'Implies --file and sets the file output pattern',
		'compress' => 'Explicitly compress the dump using gzip (new option)',
		'no-compress' => '(deprecated) Prevent compression of the archive',
		'dir' => 'Place the file in this directory',
		'non-blocking' => 'Pass the non-blocking option to the database dump command',
		'arg-prefix' => 'Pass options to prefix command-line',
		'arg-suffix' => 'Pass options to suffix command-line',
		'tables' => 'List of tables to dump',
		'*' => 'string',
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see Command::run()
	 */
	public function run(): int {
		$dbname = $this->option('name');
		$db = $this->application->databaseRegistry($dbname);
		if (!$db) {
			$this->error("No such database for \"$dbname\"\n");
			return self::EXIT_CODE_ENVIRONMENT;
		}
		[$binary, $args] = $db->shellCommand([
			'sql-dump-command' => true,
			'tables' => $this->optionIterable('tables'),
			'non-blocking' => $this->optionBool('non-blocking'),
		]);
		if ($this->hasOption('arg-prefix')) {
			$args = array_merge($this->optionIterable('arg-prefix'), $args);
		}
		$full_command_path = $this->application->paths->which($binary);
		if (!$full_command_path) {
			$this->error("Unable to find shell $binary in system path:" . implode(', ', $this->application->paths->command()) . "\n");
			return self::EXIT_CODE_ENVIRONMENT;
		}

		$suffix = '';
		$where = '';
		if ($this->optionBool('file') || $this->hasOption('dir') || $this->hasOption('target')) {
			$app_root = $this->application->path();
			$map = [
				'database_name' => $db->url('name'),
				'database_host' => $db->url('host'),
				'database_port' => $db->url('port'),
				'database_user' => $db->url('user'),
				'zesk_application_root' => $app_root,
				'application_root' => $app_root,
			];
			$dir = $this->option('dir', 'sql-dumps');
			if ($this->hasOption('target')) {
				$target = $this->option('target');
			} else {
				$target = path($dir, '{database_name}-{YYYY}-{MM}-{DD}_{hh}-{mm}.sql');
			}
			if (empty($target)) {
				$this->usage('Database target file not specified?');
				return -1;
			}
			if ($this->hasOption('compress')) {
				$compress = true;
			} else {
				$compress = !$this->optionBool('no-compress', false);
			}
			$where = map($target, $map);
			$where = Timestamp::now()->format($this->application->locale, $where);
			$suffix = '';
			if ($compress || File::extension($where) === 'gz') {
				if (!$compress) {
					$this->application->logger->warning('target dump file has .gz extension, so ignoring --compress flag');
				} else {
					$where .= '.gz';
				}
				$suffix .= ' | gzip';
			}
			$where = Directory::make_absolute($app_root, $where);
			$suffix .= " > $where";
		}

		if ($this->hasOption('arg-suffix')) {
			$args = array_merge($args, explode(' ', $this->option('arg-suffix')));
		}

		$command_line = $full_command_path . implode('', ArrayTools::prefixValues($args, ' ')) . $suffix;
		if ($this->optionBool('echo')) {
			echo $command_line . "\n";
			return self::EXIT_CODE_SUCCESS;
		}
		if ($where) {
			echo "Outputting to $where ... ";
			exec($command_line);
			echo "done.\n";
		} else {
			PHP::requires('pcntl', true);
			$func = 'pcntl_exec';
			$func($full_command_path, $args);
		}
		return self::EXIT_CODE_SUCCESS;
	}
}
