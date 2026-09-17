<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Dev-only helper: instantiates a model and calls one of its methods against
 * the real configured database, then dumps the result. Not a REPL — just a
 * fast way to sanity-check a query without wiring up a route or writing a test.
 */
class TryModel extends BaseCommand
{
	protected $group       = 'Database';
	protected $name        = 'db:try';
	protected $description = 'Calls a model method against the real database and dumps the result. Dev tool only.';
	protected $usage       = 'db:try <Model> <method> [args]';
	protected $arguments   = [
		'Model'  => 'Model class name, e.g. CarhrisUserModel (assumed under App\\Models unless fully qualified)',
		'method' => 'The method to call on the model',
		'args'   => 'Optional JSON array of positional arguments, e.g. \'[{"username":"jdelacruz"},1,5]\'',
	];

	public function run(array $params)
	{
		if (ENVIRONMENT === 'production')
		{
			CLI::error('db:try is a development tool and is disabled when CI_ENVIRONMENT=production.');
			return;
		}

		$modelName = array_shift($params);
		$method    = array_shift($params);
		$argsJson  = array_shift($params) ?? '[]';

		if (empty($modelName) || empty($method))
		{
			CLI::error('Usage: ' . $this->usage);
			return;
		}

		$args = json_decode($argsJson, true);

		if (json_last_error() !== JSON_ERROR_NONE)
		{
			CLI::error('Could not parse args as JSON: ' . json_last_error_msg());
			return;
		}

		$fqcn = strpos($modelName, '\\') !== false ? $modelName : "App\\Models\\{$modelName}";

		if (! class_exists($fqcn))
		{
			CLI::error("Model class not found: {$fqcn}");
			return;
		}

		$model = new $fqcn();

		if (! method_exists($model, $method))
		{
			CLI::error("Method not found: {$fqcn}::{$method}()");
			return;
		}

		CLI::write("Calling {$fqcn}::{$method}(" . implode(', ', array_map('json_encode', $args)) . ')', 'yellow');
		CLI::newLine();

		try
		{
			$result = $model->{$method}(...$args);
		}
		catch (Throwable $e)
		{
			$this->showError($e);
			return;
		}

		CLI::write('Result:', 'green');
		print_r($result);
		CLI::newLine();
	}
}
