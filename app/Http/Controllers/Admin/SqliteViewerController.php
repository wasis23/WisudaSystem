<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use PDO;
use Exception;

class SqliteViewerController extends Controller
{
    /**
     * Check if a path is within the allowed application directory.
     */
    protected function isAllowedPath(string $path): bool
    {
        $base = realpath(base_path());
        $real = realpath($path);

        if ($real !== false && $base !== false) {
            return str_starts_with($real, $base);
        }

        // If file doesn't exist yet, check its directory
        $dir = realpath(dirname($path));
        if ($dir !== false && $base !== false) {
            return str_starts_with($dir, $base);
        }

        return false;
    }

    /**
     * Get default and detected SQLite database paths within project scope.
     */
    protected function getDetectedDatabases(): array
    {
        $databases = [];

        // 1. Primary project database: database/database.sqlite
        $defaultDb = database_path('database.sqlite');
        if (File::exists($defaultDb)) {
            $databases[] = [
                'name' => 'database/database.sqlite (Database Utama Project)',
                'path' => $defaultDb,
                'size' => File::size($defaultDb),
                'modified_at' => date('Y-m-d H:i:s', File::lastModified($defaultDb)),
                'is_default' => true,
            ];
        }

        // 2. Scan database/ directory for other .sqlite or .db files
        $dbDirFiles = File::glob(database_path('*.{sqlite,sqlite3,db}'), GLOB_BRACE);
        if ($dbDirFiles) {
            foreach ($dbDirFiles as $file) {
                if ($file !== $defaultDb && File::exists($file) && $this->isAllowedPath($file)) {
                    $databases[] = [
                        'name' => basename($file),
                        'path' => $file,
                        'size' => File::size($file),
                        'modified_at' => date('Y-m-d H:i:s', File::lastModified($file)),
                        'is_default' => false,
                    ];
                }
            }
        }

        // 3. Scan storage/app/sqlite_uploads/ directory
        $uploadDir = storage_path('app/sqlite_uploads');
        if (!File::exists($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $uploadedFiles = File::glob($uploadDir . '/*.{sqlite,sqlite3,db}', GLOB_BRACE);
        if ($uploadedFiles) {
            foreach ($uploadedFiles as $file) {
                if (File::exists($file) && $this->isAllowedPath($file)) {
                    $databases[] = [
                        'name' => '[Upload] ' . basename($file),
                        'path' => $file,
                        'size' => File::size($file),
                        'modified_at' => date('Y-m-d H:i:s', File::lastModified($file)),
                        'is_default' => false,
                        'is_uploaded' => true,
                    ];
                }
            }
        }

        return $databases;
    }

    /**
     * Create a PDO connection for SQLite file.
     */
    protected function getPdo(string $dbPath): PDO
    {
        if (!$this->isAllowedPath($dbPath)) {
            throw new Exception("Akses ke path database ini tidak diizinkan.");
        }

        if (!File::exists($dbPath)) {
            // Auto create empty file if it was the default one
            if ($dbPath === database_path('database.sqlite')) {
                File::put($dbPath, '');
            } else {
                throw new Exception("File database SQLite tidak ditemukan pada path: {$dbPath}");
            }
        }

        $pdo = new PDO("sqlite:" . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    /**
     * Main SQLite Viewer Page
     */
    public function index(Request $request)
    {
        $detectedDatabases = $this->getDetectedDatabases();

        // Selected DB path - default to database/database.sqlite
        $selectedDbPath = $request->input('db');
        if (!$selectedDbPath || !$this->isAllowedPath($selectedDbPath) || !File::exists($selectedDbPath)) {
            $selectedDbPath = database_path('database.sqlite');
        }

        $dbInfo = [
            'path' => $selectedDbPath,
            'filename' => basename($selectedDbPath),
            'display_path' => 'database/database.sqlite',
            'size' => File::exists($selectedDbPath) ? File::size($selectedDbPath) : 0,
            'size_formatted' => File::exists($selectedDbPath) ? $this->formatBytes(File::size($selectedDbPath)) : '0 B',
            'last_modified' => File::exists($selectedDbPath) ? date('Y-m-d H:i:s', File::lastModified($selectedDbPath)) : '-',
            'is_writable' => File::exists($selectedDbPath) ? is_writable($selectedDbPath) : false,
            'sqlite_version' => 'Unknown',
            'total_tables' => 0,
            'total_views' => 0,
        ];

        $tables = [];
        $selectedTable = $request->input('table');
        $tableData = null;
        $tableSchema = null;
        $tableIndexes = [];
        $tableForeignKeys = [];
        $tableSql = null;
        $errorMessage = null;

        try {
            $pdo = $this->getPdo($selectedDbPath);

            // Get SQLite Version
            $verStmt = $pdo->query("SELECT sqlite_version()");
            if ($verStmt) {
                $dbInfo['sqlite_version'] = $verStmt->fetchColumn();
            }

            // Get all tables and views
            $masterStmt = $pdo->query("
                SELECT name, type, sql 
                FROM sqlite_master 
                WHERE type IN ('table', 'view') 
                  AND name NOT LIKE 'sqlite_%' 
                ORDER BY type ASC, name ASC
            ");
            $rawTables = $masterStmt ? $masterStmt->fetchAll() : [];

            foreach ($rawTables as $item) {
                $tableName = $item['name'];
                $itemType = $item['type'];

                $rowCount = 0;
                $colCount = 0;

                try {
                    // Quick count
                    $countStmt = $pdo->query("SELECT COUNT(*) FROM \"{$tableName}\"");
                    $rowCount = $countStmt ? (int)$countStmt->fetchColumn() : 0;

                    // Column count
                    $colsStmt = $pdo->query("PRAGMA table_info(\"{$tableName}\")");
                    $cols = $colsStmt ? $colsStmt->fetchAll() : [];
                    $colCount = count($cols);
                } catch (\Exception $e) {
                    $rowCount = -1;
                }

                $tables[] = [
                    'name' => $tableName,
                    'type' => $itemType,
                    'sql' => $item['sql'],
                    'row_count' => $rowCount,
                    'column_count' => $colCount,
                ];

                if ($itemType === 'table') {
                    $dbInfo['total_tables']++;
                } else {
                    $dbInfo['total_views']++;
                }
            }

            // If no table selected, default to 'wisudawan' or first table
            if (!$selectedTable && count($tables) > 0) {
                $hasWisudawan = collect($tables)->firstWhere('name', 'wisudawan');
                $selectedTable = $hasWisudawan ? 'wisudawan' : $tables[0]['name'];
            }

            // If a table is selected, load its details
            if ($selectedTable) {
                // Find table metadata
                $currentTableMeta = collect($tables)->firstWhere('name', $selectedTable);
                if ($currentTableMeta) {
                    $tableSql = $currentTableMeta['sql'];
                }

                // Table Schema
                $colsStmt = $pdo->query("PRAGMA table_info(\"{$selectedTable}\")");
                $tableSchema = $colsStmt ? $colsStmt->fetchAll() : [];

                // Table Indexes
                $idxStmt = $pdo->query("PRAGMA index_list(\"{$selectedTable}\")");
                $rawIndexes = $idxStmt ? $idxStmt->fetchAll() : [];
                foreach ($rawIndexes as $idx) {
                    $idxInfoStmt = $pdo->query("PRAGMA index_info(\"{$idx['name']}\")");
                    $idxCols = $idxInfoStmt ? $idxInfoStmt->fetchAll() : [];
                    $tableIndexes[] = [
                        'name' => $idx['name'],
                        'unique' => (bool)$idx['unique'],
                        'origin' => $idx['origin'] ?? 'c',
                        'partial' => (bool)($idx['partial'] ?? false),
                        'columns' => array_column($idxCols, 'name'),
                    ];
                }

                // Foreign Keys
                $fkStmt = $pdo->query("PRAGMA foreign_key_list(\"{$selectedTable}\")");
                $tableForeignKeys = $fkStmt ? $fkStmt->fetchAll() : [];

                // Table Data pagination & filtering
                $search = trim($request->input('search', ''));
                $perPage = max(5, min(200, (int)$request->input('per_page', 15)));
                $page = max(1, (int)$request->input('page', 1));
                $sortBy = $request->input('sort_by');
                $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'DESC' : 'ASC';

                $whereClause = '';
                $params = [];

                // If searching, build WHERE across columns
                if ($search !== '' && !empty($tableSchema)) {
                    $searchClauses = [];
                    foreach ($tableSchema as $col) {
                        $colName = $col['name'];
                        $searchClauses[] = "\"{$colName}\" LIKE :search_{$colName}";
                        $params[":search_{$colName}"] = "%{$search}%";
                    }
                    if (!empty($searchClauses)) {
                        $whereClause = ' WHERE (' . implode(' OR ', $searchClauses) . ')';
                    }
                }

                // Count total rows matching filter
                $countSql = "SELECT COUNT(*) FROM \"{$selectedTable}\"" . $whereClause;
                $countStmt = $pdo->prepare($countSql);
                $countStmt->execute($params);
                $totalRows = (int)$countStmt->fetchColumn();

                // Order by
                $orderClause = '';
                if ($sortBy && collect($tableSchema)->contains('name', $sortBy)) {
                    $orderClause = " ORDER BY \"{$sortBy}\" {$sortDir}";
                }

                // Data query with Limit & Offset
                $offset = ($page - 1) * $perPage;
                $dataSql = "SELECT * FROM \"{$selectedTable}\"" . $whereClause . $orderClause . " LIMIT {$perPage} OFFSET {$offset}";
                $dataStmt = $pdo->prepare($dataSql);
                $dataStmt->execute($params);
                $rows = $dataStmt->fetchAll();

                $tableData = [
                    'rows' => $rows,
                    'total' => $totalRows,
                    'page' => $page,
                    'per_page' => $perPage,
                    'last_page' => (int)ceil($totalRows / $perPage),
                    'from' => $totalRows > 0 ? $offset + 1 : 0,
                    'to' => min($offset + $perPage, $totalRows),
                    'search' => $search,
                    'sort_by' => $sortBy,
                    'sort_dir' => $sortDir,
                ];
            }
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }

        return Inertia::render('Admin/SqliteViewer/Index', [
            'detectedDatabases' => $detectedDatabases,
            'selectedDb' => $selectedDbPath,
            'dbInfo' => $dbInfo,
            'tables' => $tables,
            'selectedTable' => $selectedTable,
            'tableSchema' => $tableSchema,
            'tableIndexes' => $tableIndexes,
            'tableForeignKeys' => $tableForeignKeys,
            'tableSql' => $tableSql,
            'tableData' => $tableData,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Sync tables & records from MySQL database to database/database.sqlite.
     */
    public function syncFromMysql(Request $request)
    {
        try {
            // Run migrations on SQLite if not run yet
            \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--database' => 'sqlite',
                '--force' => true,
            ]);

            $mysqlTables = DB::connection('mysql')->select('SHOW TABLES');
            $sqliteTables = array_column(DB::connection('sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"), 'name');

            DB::connection('sqlite')->statement('PRAGMA foreign_keys = OFF');

            $totalSynced = 0;
            foreach ($mysqlTables as $t) {
                $arr = (array)$t;
                $tableName = reset($arr);

                if (in_array($tableName, $sqliteTables)) {
                    DB::connection('sqlite')->table($tableName)->truncate();

                    $rows = DB::connection('mysql')->table($tableName)->get();
                    $insertData = [];
                    foreach ($rows as $r) {
                        $insertData[] = (array)$r;
                        if (count($insertData) >= 100) {
                            DB::connection('sqlite')->table($tableName)->insert($insertData);
                            $insertData = [];
                        }
                    }
                    if (!empty($insertData)) {
                        DB::connection('sqlite')->table($tableName)->insert($insertData);
                    }
                    $totalSynced += count($rows);
                }
            }

            DB::connection('sqlite')->statement('PRAGMA foreign_keys = ON');

            return redirect()->back()->with('success', "Berhasil menyinkronkan {$totalSynced} data dari database MySQL ke database/database.sqlite.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', "Gagal sinkronisasi: " . $e->getMessage());
        }
    }

    /**
     * Execute custom SQL query and return results.
     */
    public function executeQuery(Request $request)
    {
        $request->validate([
            'db' => 'required|string',
            'query' => 'required|string',
        ]);

        $dbPath = $request->input('db');
        $query = trim($request->input('query'));

        try {
            $pdo = $this->getPdo($dbPath);

            $startTime = microtime(true);
            $isSelectLike = preg_match('/^\s*(SELECT|PRAGMA|EXPLAIN)\b/i', $query);

            if ($isSelectLike) {
                // If it's a SELECT/PRAGMA/EXPLAIN query, fetch rows (limit max 500 rows for browser performance)
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $rows = [];
                $count = 0;
                $maxLimit = 500;

                while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) && $count < $maxLimit) {
                    $rows[] = $row;
                    $count++;
                }

                $columns = !empty($rows) ? array_keys($rows[0]) : [];
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                return response()->json([
                    'success' => true,
                    'is_select' => true,
                    'columns' => $columns,
                    'rows' => $rows,
                    'total_returned' => count($rows),
                    'has_more' => $count >= $maxLimit,
                    'execution_time_ms' => $executionTime,
                ]);
            } else {
                // INSERT / UPDATE / DELETE / CREATE / DROP
                $affected = $pdo->exec($query);
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                return response()->json([
                    'success' => true,
                    'is_select' => false,
                    'affected_rows' => $affected,
                    'execution_time_ms' => $executionTime,
                    'message' => "Query berhasil dieksekusi. ({$affected} baris terpengaruh)",
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Export table or query to CSV / JSON download.
     */
    public function export(Request $request)
    {
        $dbPath = $request->input('db');
        $table = $request->input('table');
        $format = strtolower($request->input('format', 'csv'));

        try {
            $pdo = $this->getPdo($dbPath);
            $stmt = $pdo->query("SELECT * FROM \"{$table}\"");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $filename = ($table ?: 'export') . '_' . date('Ymd_His') . '.' . $format;

            if ($format === 'json') {
                return Response::make(
                    json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    200,
                    [
                        'Content-Type' => 'application/json',
                        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                    ]
                );
            }

            // CSV Export
            $csvHeaders = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($rows) {
                $handle = fopen('php://output', 'w');
                // Output UTF-8 BOM for Excel compatibility
                fputs($handle, "\xEF\xBB\xBF");

                if (!empty($rows)) {
                    // Header row
                    fputcsv($handle, array_keys($rows[0]));
                    // Data rows
                    foreach ($rows as $row) {
                        fputcsv($handle, array_map(function ($val) {
                            if (is_array($val) || is_object($val)) {
                                return json_encode($val);
                            }
                            return $val;
                        }, $row));
                    }
                }
                fclose($handle);
            };

            return response()->stream($callback, 200, $csvHeaders);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal export data: ' . $e->getMessage());
        }
    }

    /**
     * Download the SQLite database file.
     */
    public function download(Request $request)
    {
        $dbPath = $request->input('db');
        if (!$this->isAllowedPath($dbPath) || !File::exists($dbPath) || !is_readable($dbPath)) {
            return redirect()->back()->with('error', 'File database tidak ditemukan atau tidak diizinkan.');
        }

        return response()->download($dbPath, basename($dbPath));
    }

    /**
     * Upload a SQLite database file to preview.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'sqlite_file' => 'required|file|max:51200', // max 50MB
        ]);

        $file = $request->file('sqlite_file');
        $originalName = $file->getClientOriginalName();
        $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);

        $uploadDir = storage_path('app/sqlite_uploads');
        if (!File::exists($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $destinationPath = $uploadDir . '/' . $safeName;
        $file->move($uploadDir, $safeName);

        return redirect()->route('admin.sqlite-viewer.index', ['db' => $destinationPath])
            ->with('success', "Database '{$originalName}' berhasil diunggah dan siap dilihat.");
    }

    /**
     * Format bytes into human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
