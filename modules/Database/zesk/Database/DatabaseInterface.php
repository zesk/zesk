<?php
declare(strict_types=1);

namespace zesk\Database;

use DateTimeZone;
use zesk\Application;
use zesk\Database\Exception\TableNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\KeyNotFound;
use zesk\CommandFailed;
use zesk\Exception\Semantics;
use zesk\Exception\SyntaxException;
use zesk\TimeoutExpired;
use zesk\Exception\Unsupported;

/**
 * Work in progress to solidify the database interface.
 *
 * @todo 2022-12
 */
interface DatabaseInterface {
	public function __construct(Application $application, string $url = '', array $options = []);

	/*========================================================================================\
	 *
	 *  Features
	 *
	\*=======================================================================================*/
	public function feature(string $feature): mixed;

	/**
	 * @return string
	 */
	public function version(): string;

	/**
	 * @param string $feature
	 * @param string|bool $set
	 * @return $this
	 * @throws KeyNotFound
	 * @throws SyntaxException
	 */
	public function setFeature(string $feature, string|bool $set): self;

	public function isReservedWord(string $word): bool;

	/*========================================================================================\
	 *
	 *  Connection
	 *
	\*=======================================================================================*/
	public function internalConnect(): void;

	public function internalDisconnect(): void;

	public function connection(): mixed;

	public function connected(): bool;

	/*========================================================================================\
	 *
	 *  Inspection
	 *
	\*=======================================================================================*/
	/**
	 * ONLY database-specific differences (look at options for example). Do not call
	 * Column::differences from within this as it will lead to an infinite loop.
	 *
	 * @param Column $self
	 * @param Column $that
	 * @return array
	 */
	public function columnDifferences(Column $self, Column $that): array;

	/**
	 * @return array
	 */
	public function listTables(): array;

	/**
	 * @param string $tableName
	 * @return bool
	 */
	public function tableExists(string $tableName): bool;

	/**
	 * @param string $tableName
	 * @return array
	 */
	public function tableInformation(string $tableName): array;

	/**
	 * @param string $tableName
	 * @return array
	 */
	public function tableColumns(string $tableName): array;

	/**
	 * Generate a Table based on inspection
	 *
	 * @param string $tableName
	 * @return Table
	 * @throws TableNotFound
	 * @throws Unsupported
	 */
	public function databaseTable(string $tableName): Table;

	/**
	 * @param string $tableName
	 * @return int
	 */
	public function bytesUsed(string $tableName = ''): int;

	/*========================================================================================\
	 *
	 * SQL
	 *
	\*=======================================================================================*/
	public function quoteName(string $text): string;

	public function quoteText(string $text): string;

	public function quoteTable(string $text): string;

	public function unquoteTable(string $text): string;

	/*========================================================================================\
	 *
	 * Parsing
	 *
	\*=======================================================================================*/
	public function parseCreateTable(string $sql, string $source = ''): Table;

	/*========================================================================================\
	 *
	 * Queries
	 *
	\*=======================================================================================*/
	public function setTimeZone(string|DateTimeZone $zone): self;

	public function timeZone(): string;

	public function selectDatabase(string $name): self;

	/**
	 * @param string $name
	 * @param int $wait_seconds
	 * @return void
	 * @throws TimeoutExpired
	 * @throws KeyNotFound
	 * @throws Semantics
	 */
	public function getLock(string $name, int $wait_seconds = 0): void;

	/**
	 * @param string $name
	 * @return void
	 * @throws KeyNotFound
	 * @throws Semantics
	 */
	public function releaseLock(string $name): void;

	public function transactionStart(): void;

	public function transactionEnd(bool $success = true): void;

	public function query(string $sql, array $options = []): QueryResult;

	public function update(string $table, array $values, array $where = [], array $options = []): QueryResult;

	public function delete(string $table, array $where = [], array $options = []): QueryResult;

	public function affectedRows(QueryResult $result): int;

	/*========================================================================================\
	 *
	 * Dump and restore
	 *
	\*=======================================================================================*/
	/**
	 * Run a database shell command to perform various actions. Valid options are:
	 *
	 * "force" boolean
	 * "sql-dump-command" boolean
	 * "tables" array
	 *
	 * @param array $options
	 * @return array Output lines if successful
	 * @throws CommandFailed
	 */
	public function shellCommand(array $options = []): array;

	/**
	 * Output a file which is a database dump of the database
	 *
	 * @param string $filename The path to where the database should be dumped
	 * @param array $options Options for dumping the database - dependent on database type
	 * @throws FilePermission
	 * @throws DirectoryPermission
	 * @throws Unsupported
	 * @throws CommandFailed
	 */
	public function dump(string $filename, array $options = []): void;

	/**
	 * Given a database file, restore the database
	 *
	 * @param string $filename A file to restore the database from
	 * @param array $options Options for dumping the database - dependent on database type
	 * @throws FilePermission
	 * @throws FileNotFound
	 * @throws Unsupported
	 * @throws CommandFailed
	 */
	public function restore(string $filename, array $options = []): void;
}
