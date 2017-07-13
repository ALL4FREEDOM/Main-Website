    <?php    
    
    defined( '_JEXEC' ) or die;         
    
    $message = 'Client for Crowdfunding Modules. This component has no '             
            . 'configuration options and only serves as a receiver of callbacks '             
            . 'from Mollie buttons in the Crowdfunding modules. It will process '             
            . 'those callbacks, so that the display, for the user, is '             
            . 'respectful and informative. It will also notify the Campaign '             
            . 'API, send an email to the backer etc.';        
    
    echo ($$message);