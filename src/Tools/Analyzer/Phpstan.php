<?php

namespace Edge\QA\Tools\Analyzer;

use Edge\QA\OutputMode;
use Symfony\Component\Process\Process;

class Phpstan extends \Edge\QA\Tools\Tool
{
    public static $SETTINGS = array(
        'optionSeparator' => ' ',
        'internalClass' => 'PHPStan\Analyser\Analyser',
        'outputMode' => OutputMode::XML_CONSOLE_OUTPUT,
        'xml' => ['phpstan.xml'],
        'errorsXPath' => '//checkstyle/file/error',
        'composer' => 'phpstan/phpstan',
        'internalDependencies' => [
            'nette/neon' => 'Nette\Neon\Neon',
        ],
    );

    public function __invoke()
    {
        $createAbsolutePaths = function (array $relativeDirs) {
            return array_values(array_filter(array_map(
                function ($relativeDir) {
                    return '%currentWorkingDirectory%/' . trim($relativeDir, '"');
                },
                $relativeDirs
            )));
        };

        $defaultConfig = $this->config->path('phpstan.standard') ?: (getcwd() . '/phpstan.neon');
        if (file_exists($defaultConfig)) {
            $config = \Nette\Neon\Neon::decode(file_get_contents($defaultConfig));
            $config['parameters'] += [
                'excludes_analyse' => [],
            ];
        } else {
            $config = [
                'parameters' => [
                    'autoload_directories' => $createAbsolutePaths($this->options->getAnalyzedDirs()),
                    'excludes_analyse'     => [],
                ],
            ];
        }

        $config['parameters']['excludes_analyse'] = array_merge(
            $config['parameters']['excludes_analyse'],
            $createAbsolutePaths($this->options->ignore->phpstan())
        );

        $phpstanConfig = "# Configuration generated in phpqa\n" . \Nette\Neon\Neon::encode($config);
        $neonFile = $this->saveDynamicConfig($phpstanConfig, 'neon');
        $this->getErrorFormatOption();
        return array(
            'analyze',
            'ansi' => '',
            $this->getErrorFormatOption() => 'checkstyle',
            'level' => $this->config->value('phpstan.level'),
            'configuration' => $neonFile,
            $this->options->getAnalyzedDirs(' '),
        );
    }

    /**
     * @return string
     */
    private function getErrorFormatOption()
    {
        $phsptanVersion = $this->getVersion();

        return $phsptanVersion && version_compare($phsptanVersion, '0.10.3', '<') ?  'errorFormat' : 'error-format';
    }

    /**
     * @return string|string[]
     */
    private function getVersion()
    {
        $command = ['phpstan', '--version'];
        $process = new Process($command);
        $process->run();
        $firstLine = strtok($process->getOutput(), "\n");

        return str_replace('PHPStan - PHP Static Analysis Tool ', '', $firstLine);
    }
}
