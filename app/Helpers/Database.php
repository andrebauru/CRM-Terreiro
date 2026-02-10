<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * A PDO-like wrapper for MySQLi to ensure portability when pdo_mysql is missing.
 */
class MySQLiPDO
{
    private \mysqli $mysqli;
    public int $lastInsertId = 0;

    public function __construct(string $host, string $user, string $pass, string $db, int $port = 3306)
    {
        $this->mysqli = new \mysqli($host, $user, $pass, $db, $port);

        if ($this->mysqli->connect_error) {
            throw new \Exception("Connection failed: " . $this->mysqli->connect_error);
        }

        $this->mysqli->set_charset("utf8mb4");
    }

    public function prepare(string $sql): MySQLiPDOStatement
    {
        return new MySQLiPDOStatement($this->mysqli, $sql);
    }

    public function query(string $sql): MySQLiPDOStatement
    {
        $result = $this->mysqli->query($sql);
        return new MySQLiPDOStatement($this->mysqli, $sql, $result instanceof \mysqli_result ? $result : null);
    }

    public function lastInsertId(): string
    {
        return (string)$this->mysqli->insert_id;
    }

    public function errorInfo(): array
    {
        return ['00000', $this->mysqli->errno, $this->mysqli->error];
    }
}

class MySQLiPDOStatement
{
    private \mysqli $mysqli;
    private string $sql;
    private ?\mysqli_result $result = null;
    private array $params = [];
    private ?\mysqli_stmt $stmt = null;

    public function __construct(\mysqli $mysqli, string $sql, ?\mysqli_result $result = null)
    {
        $this->mysqli = $mysqli;
        $this->sql = $sql;
        $this->result = $result;
    }

    public function bindParam($param, &$var, $type = null): bool
    {
        $this->params[$param] = &$var;
        return true;
    }

    public function bindValue($param, $value, $type = null): bool
    {
        $this->params[$param] = $value;
        return true;
    }

    public function execute(?array $params = null): bool
    {
        if ($params) {
            foreach ($params as $key => $value) {
                $this->params[$key] = $value;
            }
        }

        $sql = $this->sql;
        $ordered_params = [];

        // Replace named parameters with ? and collect values in order
        $sql = preg_replace_callback('/:[a-zA-Z0-9_]+/', function ($matches) use (&$ordered_params) {
            $token = $matches[0];
            $key = ltrim($token, ':');
            if (array_key_exists($token, $this->params)) {
                $ordered_params[] = $this->params[$token];
            } elseif (array_key_exists($key, $this->params)) {
                $ordered_params[] = $this->params[$key];
            } else {
                // If not found, maybe it's not a parameter or just missing
                $ordered_params[] = null;
            }
            return '?';
        }, $sql);

        $this->stmt = $this->mysqli->prepare($sql);

        if (!$this->stmt) {
            throw new \Exception("Prepare failed: " . $this->mysqli->error . " | SQL: " . $sql);
        }

        if (!empty($ordered_params)) {
            $types = "";
            foreach ($ordered_params as $param) {
                if (is_int($param)) {
                    $types .= "i";
                } elseif (is_float($param) || is_double($param)) {
                    $types .= "d";
                } else {
                    $types .= "s";
                }
            }
            
            // mysqli_stmt::bind_param requires references. 
            // We create a new array of references for the values in $ordered_params.
            $bind_names = [$types];
            for ($i = 0; $i < count($ordered_params); $i++) {
                $bind_name = 'bind' . $i;
                $$bind_name = $ordered_params[$i];
                $bind_names[] = &$$bind_name;
            }
            
            call_user_func_array([$this->stmt, 'bind_param'], $bind_names);
        }

        if (!$this->stmt->execute()) {
            throw new \Exception("Execute failed: " . $this->stmt->error);
        }

        $this->result = $this->stmt->get_result();
        return true;
    }

    public function fetch($fetch_style = null): array|false
    {
        if ($this->result) {
            return $this->result->fetch_assoc() ?: false;
        }
        return false;
    }

    public function fetchAll($fetch_style = null): array
    {
        if ($this->result) {
            return $this->result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    public function fetchColumn(int $column_number = 0): mixed
    {
        $row = $this->fetch();
        if ($row) {
            $values = array_values($row);
            return $values[$column_number] ?? false;
        }
        return false;
    }

    public function rowCount(): int
    {
        return $this->stmt ? (int)$this->stmt->affected_rows : ($this->result ? $this->result->num_rows : 0);
    }
}
