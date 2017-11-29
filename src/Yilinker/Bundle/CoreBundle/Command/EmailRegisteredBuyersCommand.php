<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections;
use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Swift_Mailer;
use Swift_Message;
use Swift_Attachment;

/**
 * Class EmailRegisteredBuyersCommand
 *
 * @package Yilinker\Bundle\CoreBundle\Command
 */
class EmailRegisteredBuyersCommand extends ContainerAwareCommand
{
    const DEFAULT_LOOKBACK_SECONDS = 86400;

    const USER_PER_ITERATION = 15;
    
    /**
     * Sets the name of the command
     */
    protected function configure()
    {
        $this->setName('yilinker:report:registered-buyers')
             ->setDescription('Emails registered buyers')
             ->addOption(
                'seconds',
                null,
                InputOption::VALUE_REQUIRED,
                'Seconds to look back'
            )
            ->addOption(
                'send_email_to',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Email recipient'
            )
            ->addOption(
                'is_verified',
                null,
                InputOption::VALUE_REQUIRED,
                'Whether the buyer should be verified, false by default'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lookbackSeconds = $input->getOption('seconds', self::DEFAULT_LOOKBACK_SECONDS);
        $lookbackSeconds = $lookbackSeconds ? $lookbackSeconds : self::DEFAULT_LOOKBACK_SECONDS;
        $emailRecipients = $input->getOption('send_email_to', null);
        $isVerifiedBuyer = $input->getOption('is_verified', null) === 'true';

        if($emailRecipients === null || count($emailRecipients) === 0){
            $output->writeln("[ERROR] Recipients are required.");
            exit();
        }

        $dateNow = Carbon::now();
        $dateFrom = Carbon::now()->subSeconds($lookbackSeconds);
        
        $container = $this->getContainer();
        $ccDeveloper = $container->getParameter('reports_dev_email');
        $imageDomain = $container->getParameter('asset_hostname');

        $em = $container->get('doctrine')->getManager();
        $phpExcelObject = $container->get('phpexcel')->createPHPExcelObject();
        $writer = $container->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        try{
            $path = $container->get('kernel')->locateResource('@YilinkerCoreBundle/Resources/reports/marketing');
        }
        catch(\Exception $e){}


        $title = time();
        $phpExcelObject->getProperties()
            ->setSubject("Verified Buyers Documents")
            ->setDescription("List of verified buyers.")
            ->setTitle($title);
        $startingRow = "5";
        $phpExcelObject->setActiveSheetIndex(0)
            ->setCellValue('A1', "Report generated on: ")
            ->setCellValue('B1', $dateNow->format('Y-m-d H:i:s'))                           
            ->setCellValue('A'.$startingRow, 'EMAIL')
            ->setCellValue('B'.$startingRow, 'DATE CREATED')
            ->setCellValue('C'.$startingRow, 'CONTACT NUMBER')
            ->setCellValue('D'.$startingRow, 'ID LOCATION')
            ->setCellValue('E'.$startingRow, 'FULLNAME')
            ->setCellValue('F'.$startingRow, 'UNIT NUMBER')
            ->setCellValue('G'.$startingRow, 'BUILDING NAME')
            ->setCellValue('H'.$startingRow, 'SUBDIVISION')
            ->setCellValue('I'.$startingRow, 'STREET NUMBER')
            ->setCellValue('J'.$startingRow, 'STREET NAME')
            ->setCellValue('K'.$startingRow, 'ZIP CODE')
            ->setCellValue('L'.$startingRow, 'PROVINCE')
            ->setCellValue('M'.$startingRow, 'CITY')
            ->setCellValue('N'.$startingRow, 'BARANGAY')
            ->setCellValue('O'.$startingRow, 'PROVINCE_ID')
            ->setCellValue('P'.$startingRow, 'CITY_ID')
            ->setCellValue('Q'.$startingRow, 'BARANGAY_ID')
            ->getStyle('A'.$startingRow.':Q'.$startingRow)->getFont()->setBold(true);

        $offset = 0;

        do{        
            $queryBuilder = $em->createQueryBuilder();
            $queryBuilder->select(array(
                "User.email as email",
                "User.dateAdded as dateCreated",
                "COALESCE(User.contactNumber,'') as contactNumber",
                "COALESCE(CONCAT('".$imageDomain."/assets/images/uploads/user_documents/',User.userId,'/', UserIdentificationCard.filename),'') as IDLocation",
                "COALESCE(CONCAT(User.firstName, ' ', User.lastName),'') as fullname",
                "COALESCE(UserAddress.unitNumber,'') AS unitnumber",
                "COALESCE(UserAddress.buildingName,'') as buildingname",
                "COALESCE(UserAddress.streetNumber,'') as streetnumber",
                "COALESCE(UserAddress.streetName,'') as streetname",
                "COALESCE(UserAddress.subdivision,'') as subdivision",
                "COALESCE(UserAddress.zipCode,'') as zipcode",
                "COALESCE(PRVC.location,'') as province",
                "COALESCE(CITY.location,'') as city",
                "COALESCE(BRGY.location,'') as barangay",
                "COALESCE(PRVC.locationId,'') as province_id",
                "COALESCE(CITY.locationId,'') as city_id",
                "COALESCE(BRGY.locationId,'') as barangay_id"
            ))
                ->from("YilinkerCoreBundle:User", "User")
                ->leftJoin('YilinkerCoreBundle:UserIdentificationCard', 
                           'UserIdentificationCard', 'WITH', 'User.userId  = UserIdentificationCard.user')
                ->leftJoin('YilinkerCoreBundle:UserAddress', 'UserAddress', 'WITH', 'User.userId  = UserAddress.user')
                ->leftJoin('YilinkerCoreBundle:Location', 'BRGY', 'WITH', 'BRGY.locationId  = UserAddress.location')
                ->leftJoin('YilinkerCoreBundle:Location', 'CITY', 'WITH', 'CITY.locationId  = BRGY.parent')
                ->leftJoin('YilinkerCoreBundle:Location', 'PRVC', 'WITH', 'PRVC.locationId  = CITY.parent')
                ->where('User.dateAdded > :dateFrom')
                ->andWhere('User.dateAdded <= :dateTo')
                ->andWhere('User.userType = :buyerType')
                ->groupBy('User.contactNumber')
                ->orderBy('User.dateAdded', 'ASC')
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('buyerType', User::USER_TYPE_BUYER)
                ->setParameter('dateTo', $dateNow)
                ->setMaxResults(self::USER_PER_ITERATION)
                ->setFirstResult($offset);


            if($isVerifiedBuyer){
                $queryBuilder->andWhere('User.isEmailVerified = :verifiedEmail')
                    ->andWhere('User.isMobileVerified = :verifiedMobile')
                    ->andWhere($queryBuilder->expr()->andx(
                        $queryBuilder->expr()->isNotNull('User.firstName'),
                        $queryBuilder->expr()->neq('User.firstName', ":blank")
                    ))
                    ->andWhere($queryBuilder->expr()->andx(
                        $queryBuilder->expr()->isNotNull('User.lastName'),
                        $queryBuilder->expr()->neq('User.lastName', ":blank")
                    ))
                    ->andWhere($queryBuilder->expr()->andx(
                        $queryBuilder->expr()->isNotNull('User.email'),
                        $queryBuilder->expr()->neq('User.email', ":blank")
                    ))
                    ->andWhere('UserAddress.userAddressId IS NOT NULL')
                    ->setParameter('verifiedEmail', true)
                    ->setParameter('verifiedMobile', true)
                    ->setParameter('blank', '');
            }
            
            $results = $queryBuilder->getQuery()->getResult();
            $numberOfResults = count($results);
            $offset += self::USER_PER_ITERATION;

            foreach($results as $result){
                $startingRow++;
                $phpExcelObject->setActiveSheetIndex(0)
                    ->setCellValue('A'.$startingRow, $result['email'])
                    ->setCellValue('B'.$startingRow, $result['dateCreated'])
                    ->setCellValue('C'.$startingRow, $result['contactNumber'])
                    ->setCellValue('D'.$startingRow, $result['IDLocation'])
                    ->setCellValue('E'.$startingRow, $result['fullname'])
                    ->setCellValue('F'.$startingRow, $result['unitnumber'])
                    ->setCellValue('G'.$startingRow, $result['buildingname'])
                    ->setCellValue('H'.$startingRow, $result['streetnumber'])
                    ->setCellValue('I'.$startingRow, $result['streetname'])
                    ->setCellValue('J'.$startingRow, $result['subdivision'])
                    ->setCellValue('K'.$startingRow, $result['zipcode'])
                    ->setCellValue('L'.$startingRow, $result['province'])
                    ->setCellValue('M'.$startingRow, $result['city'])
                    ->setCellValue('N'.$startingRow, $result['barangay'])
                    ->setCellValue('O'.$startingRow, $result['province_id'])
                    ->setCellValue('P'.$startingRow, $result['city_id'])
                    ->setCellValue('Q'.$startingRow, $result['barangay_id']);
            }
        }
        while($numberOfResults > 0);


        foreach (range('A', $phpExcelObject->getActiveSheet()->getHighestDataColumn()) as $col) {
            $phpExcelObject->getActiveSheet()
                           ->getColumnDimension($col)
                           ->setAutoSize(true);
        } 

        $filename = $path.DIRECTORY_SEPARATOR.$title.".xls";
        $writer->save($filename);
        $output->writeln("File generated: ".$filename);

        $mailer = $container->get('mailer');
        $mailerEmail = $container->getParameter('mailer_user');
        $message = Swift_Message::newInstance();
        
        $message->setSubject("Registered buyers from ".$dateFrom." to ".$dateNow)
                ->setFrom($mailerEmail)
                ->addCc($ccDeveloper)
                ->setTo($emailRecipients)    
                ->attach(Swift_Attachment::fromPath($filename));
        $mailer->send($message);

        $output->writeln("Email sent to ".implode($emailRecipients,","));
    }

}
