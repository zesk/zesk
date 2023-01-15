<?php declare(strict_types=1);

namespace zesk;

use DateTimeZone;
use zesk\Database\QueryResult;

/**
 * Work in progress to solidify the database interface.
 *
 * @todo 2022-12
 */
interface Database_Interface {
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
	 * @throws Exception_Key
	 * @throws Exception_Invalid
	 * @throws Exception_Syntax
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
	 * Database_Column::differences from within this as it will lead to an infinite loop.
	 *
	 * @param Database_Column $self
	 * @param Database_Column $that
	 * @return array
	 */
	public function columnDifferences(Database_Column $self, Database_Column $that): array;

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
	 * Generate a Database_Table based on inspection
	 *
	 * @param string $tableName
	 * @return Database_Table
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Unsupported
	 */
	public function databaseTable(string $tableName): Database_Table;

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
	public function parseCreateTable(string $sql, string $source = ''): Database_Table;

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
	 * @throws Exception_Timeout
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function getLock(string $name, int $wait_seconds = 0): void;

	/**
	 * @param string $name
	 * @return void
	 * @throws Exception_Key
	 * @throws Exception_Semantics
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
	 * @throws Exception_Command
	 */
	public function shellCommand(array $options = []): array;

	/**
	 * Output a file which is a database dump of the database
	 *
	 * @param string $filename The path to where the database should be dumped
	 * @param array $options Options for dumping the database - dependent on database type
	 * @throws Exception_File_Permission
	 * @throws Exception_Directory_Permission
	 * @throws Exception_Unsupported
	 * @throws Exception_Command
	 */
	public function dump(string $filename, array $options = []): void;

	/**
	 * Given a database file, restore the database
	 *
	 * @param string $filename A file to restore the database from
	 * @param array $options Options for dumping the database - dependent on database type
	 * @throws Exception_File_Permission
	 * @throws Exception_File_NotFound
	 * @throws Exception_Unsupported
	 * @throws Exception_Command
	 */
	public function restore(string $filename, array $options = []): void;
}
