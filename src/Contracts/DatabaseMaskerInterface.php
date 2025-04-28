<?php

declare(strict_types=1);

namespace Hristijans\DatabaseMasker\Contracts;

interface DatabaseMaskerInterface
{
    /**
     * Create masked database dumps for all configured connections.
     *
     * @param  string|null  $outputPath  Base path for output files
     * @return array<string, array{status: string, connection: string, output_file?: string, tables_processed?: int, error?: string}>
     */
    public function createMaskedDumps(?string $outputPath = null): array;

    /**
     * Create a masked database dump for a specific connection.
     *
     * @param  array<string, mixed>  $connectionConfig
     * @return array{status: string, connection: string, output_file?: string, tables_processed?: int, error?: string}
     */
    public function createMaskedDumpForConnection(string $connectionName, array $connectionConfig, ?string $outputFile = null): array;

    /**
     * Create a masked database dump for default connection.
     *
     * This method is kept for backward compatibility.
     *
     * @return string Path to the created dump file
     */
    public function createMaskedDump(?string $outputFile = null): string;

    /**
     * Restore the masked database dump.
     */
    public function restoreMaskedDump(?string $inputFile = null, ?string $connectionName = null): bool;
}
