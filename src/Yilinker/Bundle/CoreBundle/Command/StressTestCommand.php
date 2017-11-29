<?php
namespace Yilinker\Bundle\CoreBundle\Command;

use RecursiveDirectoryIterator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Yilinker\Bundle\CoreBundle\Entity\Product;


/**
 * Stress test Command
 *
 * @package Yilinker\Bundle\FrontendBundle\Command
 */
class StressTestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('yilinker:execute:stress-test')
            ->setDescription('Application Stress Test')
            
            ->addOption('hostname',null,InputOption::VALUE_OPTIONAL,'hostname',null)
            ->addOption('no_request',null,InputOption::VALUE_OPTIONAL,'No of User Request',1)
            ->addOption('concurrent',null,InputOption::VALUE_OPTIONAL,'No of Concurrent Request',1)
            ->addOption('timeout',null,InputOption::VALUE_OPTIONAL,'Timeout in second',60)
            ->addOption('product_id',null, InputOption::VALUE_OPTIONAL,'Sample ProductId',2396)
            ->addOption('email',null,InputOption::VALUE_OPTIONAL,'Login Email','buyer@yilinker.com')
            ->addOption('password',null,InputOption::VALUE_OPTIONAL,'User Password','password')
            ->addOption('section',null,InputOption::VALUE_OPTIONAL,'Section of page','home')
            ->addOption('search',null,InputOption::VALUE_OPTIONAL,'Search Product','a')
            ->addOption('slug',null,InputOption::VALUE_OPTIONAL,'Product Item','alvin-test-2-bang-bang')
            ->setHelp(<<<EOF
<info>--section=[home,checkout,search,item,categories]</info>
<info>--section=checkout --email=buyer@yilinker.com --password=password --product_id=22396</info>
<info>--section=search --search=test</info>
<info>--section=item --slug=alvin-test-2-bang-bang</info>
EOF
                )
            ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->filesystem = $this->getContainer()->get('filesystem');
        $this->hostname = is_null($input->getOption('hostname')) ? $this->getContainer()->getParameter('frontend_hostname') : $input->getOption('hostname');

        $section = $input->getOption("section");

        if ($section == 'home') {
            $this->processHomepage($input,$output);
        }
        else if ($section == 'checkout') {
            $this->processCheckout($input,$output);
        }
        else if ($section == 'search') {
            $this->processSearch($input,$output);
        }
        else if ($section == 'item') {
            $this->processItems($input,$output);
        }
        else if ($section == 'categories') {
            $this->processCategories($input,$output);
        }

    }                                                                                                                                               


    protected function processHomepage($input,$output)
    {
        $no_request = $input->getOption("no_request");
        $concurrent = $input->getOption("concurrent");
        $timeout = $input->getOption("timeout");
        
        $home = "ab -n {$no_request} -s {$timeout} -k -c {$concurrent} {$this->hostname}:80/";
        $h = shell_exec($home);
        echo $h;
        $output->writeln("<info> home page request executed.</info>");

    }   

    protected function processCategories($input,$output)
    {
        $no_request = $input->getOption("no_request");
        $concurrent = $input->getOption("concurrent");
        $timeout = $input->getOption("timeout");

        $ategories = "ab -n {$no_request} -s {$timeout} -k -c {$concurrent} {$this->hostname}:80/categories";
        $c = shell_exec($ategories);
        echo $c;
        $output->writeln("<info> categories page request executed.</info>");   
    }


    protected function processSearch($input,$output)
    {
        $no_request = $input->getOption("no_request");
        $concurrent = $input->getOption("concurrent");
        $search = $input->getOption("search");
        $timeout = $input->getOption("timeout");

        $searchProducts = "ab -n {$no_request} -s {$timeout} -k -c {$concurrent} {$this->hostname}:80/search/product?query=".$search;
        $c = shell_exec($searchProducts);
        echo $c;
        $output->writeln("<info> product search request executed.</info>");   

    }

    protected function processItems($input,$output)
    {
        $no_request = $input->getOption("no_request");
        $concurrent = $input->getOption("concurrent");
        $search = $input->getOption("search");
        $timeout = $input->getOption("timeout");
        $slug = $input->getOption("slug");

        $item = "ab -n {$no_request} -s {$timeout} -k -c {$concurrent} {$this->hostname}:80/item/".$slug;
        $c = shell_exec($item);
        echo $c;
        $output->writeln("<info> Product Item request executed.</info>");   

    }

    protected function processCheckout($input,$output)
    {

        $no_request = $input->getOption("no_request");
        $concurrent = $input->getOption("concurrent");
        $timeout = $input->getOption("timeout");
        $product_id = $input->getOption("product_id");
        $email = $input->getOption("email");
        $password = $input->getOption("password");

        $entityManager = $this->getContainer()->get('doctrine')
            ->getEntityManager();

        $productRepo = $entityManager->getRepository('YilinkerCoreBundle:Product');
        $product = $productRepo->findProductByIdCached($product_id);
        
        if (is_null($product)){
            $output->writeln("<error> Product is null.</error>");
            exit;
        }

        $unitId = $product->getDefaultUnit()->getProductUnitId();

        //login
        $auth = $entityManager->getRepository('YilinkerCoreBundle:OauthClient')->find(1);
        $paramsLogin = http_build_query(array(
            'client_id' =>  $auth->getId().'_'.$auth->getRandomId(),
            'client_secret' => $auth->getSecret(),
            'email' => $email,
            'password' => $password,
            'grant_type' => 'http://yilinker-online.com/grant/buyer'
        ));

        $accessToken = json_decode($this->curl($this->hostname.'/api/v1/login', $paramsLogin));

        if (isset($accessToken->error)) {
            $output->writeln("<error> ".$accessToken->error_description."</error>");
            exit;
        }

        $this->filesystem->dumpFile('post_data',$paramsLogin);
        $login = "ab -n {$no_request} -s {$timeout} -k -c {$concurrent} -p post_data -T 'application/x-www-form-urlencoded' {$this->hostname}:80/api/v1/login";
        $x = shell_exec($login);
        echo $x;
        $output->writeln("<info> login executed.</info>");

        // update cartitem
        $params = http_build_query(array(
            'access_token' => $accessToken->access_token,
            'productId' => $product->getProductId(),
            'quantity' => 1,
            'unitId' => $unitId
        ));

        //$cartResponse = json_decode($this->curl($this->hostname.'/api/v1/auth/cart/updateCartItem', $params));
        $this->filesystem->dumpFile('post_data',$params);
        $updatecartitem = "ab -n {$no_request} -s {$timeout} -k -c {$concurrent} -p post_data -T 'application/x-www-form-urlencoded' {$this->hostname}:80/api/v1/auth/cart/updateCartItem";
        $u = shell_exec($updatecartitem);
        echo $u;
        $output->writeln("<info> Add Cart Item Executed.</info>");

        //payment
        $paramsPayment = http_build_query(array(
            'access_token' => $accessToken->access_token,
        ));
        $this->filesystem->dumpFile('post_data',$paramsPayment);
        $doPaymentCod = "ab -n {$no_request} -k -s {$timeout} -c {$concurrent} -p post_data -T 'application/x-www-form-urlencoded' {$this->hostname}:80/api/v2/auth/payment/doPesoPay";
        $p = shell_exec($doPaymentCod);
        echo $p;

        $output->writeln("<info> Payment Executed.</info>");
    }

    private function curl($url,$params)                                                            
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 60,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS =>  $params, 
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/x-www-form-urlencoded",
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
          exit;

        } else {
          return $response;
        }
    }

    
}
