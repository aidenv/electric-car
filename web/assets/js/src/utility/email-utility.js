
   /**
    * Determines if an email has valid format  
    * 
    * @param string email
    * @return boolean
    */
    function isEmailValid(email)
    {
        var emailRegex = /^[_a-z0-9-]+(.[_a-z0-9-\+]+)@[a-z0-9-]+(.[a-z0-9-]+)(.[a-z]{2,3})$/;
        return emailRegex.test(email);
    }
