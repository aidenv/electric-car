<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Catalogue\DiffOperation;
use Symfony\Component\Translation\Catalogue\MergeOperation;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;

use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidReferralCode;
use Yilinker\Bundle\BackendBundle\Form\Validator\Constraints\ValidNotification;

use Carbon\Carbon;

class FormErrorTranslationCommand extends ContainerAwareCommand
{
    private $messages = array();

    protected function configure()
    {
        $this
            ->setName("yilinker:form:translate")
            ->setDescription("Generates form error translation for a certain bundle. YML file must be Resource/config/form.yml")
            ->setDefinition(array(
                new InputArgument("locale", InputArgument::REQUIRED, "The locale"),
                new InputArgument("bundle", InputArgument::REQUIRED, "The bundle name or directory where to load the messages"),
                new InputOption("force", null, InputOption::VALUE_NONE, "Should the update be done"),
            ))
            ->setHelp(<<<EOF
Must clear cache upon executing this command.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locale = $input->getArgument("locale");
        $bundle = $input->getArgument("bundle");

        $container = $this->getContainer();
        $kernel = $container->get("kernel");

        $bundleCollection = $container->getParameter("kernel.bundles");

        if(array_key_exists($bundle, $bundleCollection)){
            $resources = $kernel->locateResource("@{$bundle}/Resources");

            $targetDocument = $resources."/translations/validators.{$locale}.xlf";
            $formConfigLocation = $resources."/config/form.yml";
            
            if(!$input->getOption("force") && file_exists($targetDocument)){
                $output->writeln("<error>Add --force option to overwrite.</error>");
            }
            else{
                
                if(file_exists($formConfigLocation)){

                    try {
                        
                        $yaml = new Parser();

                        $extractedCatalogue = new MessageCatalogue($locale);
                        $currentCatalogue = new MessageCatalogue($locale);

                        $services = $yaml->parse(file_get_contents($formConfigLocation))["services"];

                        foreach ($services as $service){
                            if(array_key_exists("tags", $service)){
                                foreach ($service["tags"] as $tag) {
                                    if(
                                        array_key_exists("alias", $tag) &&
                                        array_key_exists("name", $tag) &&
                                        $tag["name"] === "form.type"
                                    ){
                                        $output->writeln("<info>Generating errors from ".$tag["alias"]."</info>");
                                        $this->disectForm($tag["alias"]);
                                    }
                                }
                            }
                        }
         
                        $output->writeln("<info>Done gathering errors from {$bundle}</info>");
                        
                        if(file_exists($targetDocument)){
                            $output->writeln("<info>Gather existing messages for {$bundle}</info>");
                            $this->constructExtractedCatalogue(
                                $extractedCatalogue, 
                                $resources."/translations", 
                                $output
                            );
                        }

                        $writer = $container->get('translation.writer');
                        $writer->disableBackup();

                        $output->writeln("<info>Preparing to write.</info>");
                        $this->constructValidatorCatalogue(
                            $currentCatalogue, 
                            $extractedCatalogue, 
                            $output
                        );

                        $operation = new MergeOperation($currentCatalogue, $extractedCatalogue);

                        $writer->writeTranslations(
                            $operation->getResult(), 
                            "xlf", 
                            array(
                                "path" => $resources."/translations", 
                                "default_locale" => "en"
                            )
                        );
     
                        $output->writeln("<info>Generating xls file for {$bundle}</info>");
                        $this->createExcelFile($bundle, $operation->getResult()->all("validators"));
                        $output->writeln("<info>Translation file for {$bundle} has been generated</info>");

                    } catch (ParseException $e) {
                        $output->writeln("<error>Unable to parse the YAML string: %s </error>", $e->getMessage());
                    }
                }
                else{
                    $output->writeln("<error>Config does not exists.<error>");
                }
            }
        }
        else{
            $output->writeln("<error>Bundle does not exists.<error>");
        }
    }

    private function constructExtractedCatalogue(
        &$extractedCatalogue, 
        $path, 
        $output
    ){
        $container = $this->getContainer();
        $loader = $container->get('translation.loader');
        $loader->loadMessages($path, $extractedCatalogue);
    }

    private function constructValidatorCatalogue(&$currentCatalogue, $extractedCatalogue, $output)
    {
        foreach($this->messages as $message){
            $messageExists = $extractedCatalogue->get($message, "validators");

            if($message === $messageExists){
                $output->writeln("<comment>Creating translation for :</comment> {$message}");
                $currentCatalogue->set($message, "__".$message, "validators");
            }
        }
    }

    private function disectForm($formName)
    {
        $container = $this->getContainer();
        $form = $container->get("form.factory")->create($formName);
        $fields = $form->all();

        foreach ($fields as $field) {
            $options = $field->getConfig()->getOptions();

            if(array_key_exists("constraints", $options)){
                $constraints = $options["constraints"];

                foreach($constraints as $constraint){
                    if($constraint instanceof ValidReferralCode){
                        $this->getValidReferralCodeMessages($constraint);
                    }
                    elseif($constraint instanceof ValidNotification){
                        $this->getValidNotificationMessages($constraint);
                    }
                    elseif($constraint instanceof File){
                        $this->getFileMessages($constraint);
                    }
                    elseif($constraint instanceof Length){
                        $this->getLengthMessages($constraint);
                    }
                    elseif($constraint instanceof Range){
                        $this->getRangeMessages($constraint);
                    }
                    elseif($constraint instanceof All){
                        $this->getAllMessages($constraint);
                    }
                    else{
                        if(property_exists($constraint, "message")){
                            $this->pushMessage($constraint->message);
                        }
                    }
                }
            }
        }
    }

    private function getValidReferralCodeMessages($constraint)
    {
        $this->pushMessage($constraint->message);
        $this->pushMessage($constraint->hasBeenReferred);
    }

    private function getValidNotificationMessages($constraint)
    {
        $this->pushMessage($constraint->message);
        $this->pushMessage($constraint->uneditable);
    }

    private function getFileMessages($constraint)
    {
        $this->pushMessage($constraint->notFoundMessage);
        $this->pushMessage($constraint->notReadableMessage);
        $this->pushMessage($constraint->maxSizeMessage);
        $this->pushMessage($constraint->mimeTypesMessage);
        $this->pushMessage($constraint->disallowEmptyMessage);
        $this->pushMessage($constraint->uploadIniSizeErrorMessage);
        $this->pushMessage($constraint->uploadFormSizeErrorMessage);
        $this->pushMessage($constraint->uploadPartialErrorMessage);
        $this->pushMessage($constraint->uploadNoFileErrorMessage);
        $this->pushMessage($constraint->uploadNoTmpDirErrorMessage);
        $this->pushMessage($constraint->uploadCantWriteErrorMessage);
        $this->pushMessage($constraint->uploadExtensionErrorMessage);
        $this->pushMessage($constraint->uploadErrorMessage);
    }

    private function getAllMessages($constraint)
    {
        foreach ($constraint->constraints as $constraintProperties){
            if($constraintProperties instanceof Image){
                $this->pushMessage($constraintProperties->mimeTypesMessage);
                $this->pushMessage($constraintProperties->sizeNotDetectedMessage);
                $this->pushMessage($constraintProperties->minWidthMessage);
                $this->pushMessage($constraintProperties->minHeightMessage);
                $this->pushMessage($constraintProperties->maxHeightMessage);
                $this->pushMessage($constraintProperties->minRatioMessage);
                $this->pushMessage($constraintProperties->maxRatioMessage);
                $this->pushMessage($constraintProperties->allowSquareMessage);
                $this->pushMessage($constraintProperties->allowLandscapeMessage);
                $this->pushMessage($constraintProperties->allowPortraitMessage);
                $this->pushMessage($constraintProperties->notFoundMessage);
                $this->pushMessage($constraintProperties->notReadableMessage);
                $this->pushMessage($constraintProperties->maxSizeMessage);
                $this->pushMessage($constraintProperties->disallowEmptyMessage);
                $this->pushMessage($constraintProperties->uploadIniSizeErrorMessage);
                $this->pushMessage($constraintProperties->uploadFormSizeErrorMessage);
                $this->pushMessage($constraintProperties->uploadPartialErrorMessage);
                $this->pushMessage($constraintProperties->uploadNoFileErrorMessage);
                $this->pushMessage($constraintProperties->uploadNoTmpDirErrorMessage);
                $this->pushMessage($constraintProperties->uploadCantWriteErrorMessage);
                $this->pushMessage($constraintProperties->uploadExtensionErrorMessage);
                $this->pushMessage($constraintProperties->uploadErrorMessage);
            }
        }
    }

    private function getRangeMessages($constraint)
    {
        $this->pushMessage($constraint->maxMessage);
        $this->pushMessage($constraint->minMessage);
        $this->pushMessage($constraint->invalidMessage);
    }

    private function getLengthMessages($constraint)
    {
        $this->pushMessage($constraint->maxMessage);
        $this->pushMessage($constraint->minMessage);
        $this->pushMessage($constraint->exactMessage);
        $this->pushMessage($constraint->charsetMessage);
    }

    private function pushMessage($message)
    {
        if(!in_array($message, $this->messages)){
            array_push($this->messages, $this->replaceValues($message));
        }
    }

    private function replaceValues($message)
    {
        $message = str_replace("{{ ", "%", $message);
        $message = str_replace(" }}", "%", $message);

        return htmlentities($message);
    }

    private function createExcelFile($bundle, $messages)
    {        
        $dateNow = Carbon::now();
        $container = $this->getContainer();      
        $kernel = $container->get('kernel'); 
        $phpExcelObject = $container->get('phpexcel')
                                    ->createPHPExcelObject();
        $writer = $container->get('phpexcel')
                            ->createWriter($phpExcelObject, 'Excel5');

        $resources = $kernel->locateResource("@{$bundle}/Resources");

        $reportsDir = $resources."/reports";
        $translationsDir = $resources."/reports/translations";

        if(!file_exists($reportsDir)){
            mkdir($reportsDir, 0777);
        }

        if(!file_exists($translationsDir)){
            mkdir($translationsDir, 0777);
        }

        $title = "translation-form-errors-{$bundle}-".time();
        $phpExcelObject->getProperties()
                       ->setSubject("Translation Form Errors")
                       ->setDescription("Translation Form Errors")
                       ->setTitle($title);

        $rowCounter = "3";
        $phpExcelObject->setActiveSheetIndex(0)
                       ->setCellValue('A1', "Translation generated on: ")
                       ->setCellValue('B1', $dateNow->format('Y-m-d H:i:s'));

        foreach($messages as $message => $translation){
            $phpExcelObject->setActiveSheetIndex(0)
                           ->setCellValue('A'.$rowCounter, $message)
                           ->setCellValue('B'.$rowCounter, $translation)
                           ->getStyle('A'.$rowCounter)->getFont()->setBold(true);

            $rowCounter++;
        }

        foreach (range('A', $phpExcelObject->getActiveSheet()->getHighestDataColumn()) as $col) {
            $phpExcelObject->getActiveSheet()
                           ->getColumnDimension($col)
                           ->setAutoSize(true);
        }

        $filename = $translationsDir.DIRECTORY_SEPARATOR.$title.".xls";
        $writer->save($filename);
        echo "File generated: ".$filename."\n";
    }
}