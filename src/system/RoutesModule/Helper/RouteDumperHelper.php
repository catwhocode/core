<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <info@ziku.la>.
 * @link https://ziku.la
 * @link https://ziku.la
 * @version Generated by ModuleStudio 1.0.0 (https://modulestudio.de).
 */

namespace Zikula\RoutesModule\Helper;

use FOS\JsRoutingBundle\Command\DumpCommand;
use JMS\I18nRoutingBundle\Router\I18nLoader;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zikula\Bundle\CoreBundle\HttpKernel\ZikulaHttpKernelInterface;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\SettingsModule\Api\ApiInterface\LocaleApiInterface;

class RouteDumperHelper
{
    /**
     * @var ZikulaHttpKernelInterface
     */
    private $kernel;

    /**
     * @var VariableApiInterface
     */
    private $variableApi;

    /**
     * @var LocaleApiInterface
     */
    private $localeApi;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var DumpCommand
     */
    private $dumpCommand;

    public function __construct(
        ZikulaHttpKernelInterface $kernel,
        VariableApiInterface $variableApi,
        LocaleApiInterface $localeApi,
        Filesystem $fileSystem,
        TranslatorInterface $translator,
        DumpCommand $dumpCommand
    ) {
        $this->kernel = $kernel;
        $this->variableApi = $variableApi;
        $this->localeApi = $localeApi;
        $this->fileSystem = $fileSystem;
        $this->translator = $translator;
        $this->dumpCommand = $dumpCommand;
    }

    /**
     * Dump the routes exposed to javascript to '/public/js/'.
     */
    public function dumpJsRoutes(string $lang = null): string
    {
        // determine list of supported languages
        $installedLanguages = $this->localeApi->getSupportedLocales();
        if (isset($lang) && in_array($lang, $installedLanguages, true)) {
            // use provided lang if available
            $langs = [$lang];
        } else {
            $multilingual = (bool) $this->variableApi->getSystemVar('multilingual');
            if ($multilingual) {
                // get all available locales
                $langs = $installedLanguages;
            } else {
                // get only the default locale
                $langs = [$this->variableApi->getSystemVar('locale', 'en')];
            }
        }

        $errors = '';
        $format = 'js';
        $domain = '';
        foreach ($langs as $locale) {
            // force deletion of existing file
            $targetPath = sprintf(
                '%s/public/js/fos_js_routes%s.%s.%s',
                $this->kernel->getProjectDir(),
                empty($domain) ? '' : ('_' . implode('_', $domain)),
                $locale,
                $format
            );
            if ($this->fileSystem->exists($targetPath)) {
                try {
                    $this->fileSystem->remove($targetPath);
                } catch (IOExceptionInterface $exception) {
                    $errors .= $this->translator->trans('Error: Could not delete "%path%" because %message%.', [
                        '%path%' => $targetPath,
                        '%message%' => $exception->getMessage()
                    ]);
                }
            }

            // call dump command
            $input = new ArrayInput([
                '--format' => $format,
                '--locale' => $locale . I18nLoader::ROUTING_PREFIX,
                '--target' => $targetPath
            ]);
            $output = new NullOutput();
            try {
                $this->dumpCommand->run($input, $output);
            } catch (RuntimeException $exception) {
                $errors .= $exception->getMessage() . '. ';
            }
        }

        return $errors;
    }
}
