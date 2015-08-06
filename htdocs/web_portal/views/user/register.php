<div class="rightPageContainer">
    <form name="Register" action="index.php?Page_Type=Register" method="post" class="inputForm"
          onsubmit="return confirm('Click OK to confirm you provide consent for your ID and user details to be re-published by GOCDB and made visible to other authenticated GOCDB services and users.');">
    	<h1>Register</h1>
    	<br />
        Register Unique Identity: <b> <?php echo($params['dn']); ?> </b>
        <br/> 
        <br/> 

        
        <div class="alert alert-warning" role="alert">
            <ul>
               <li>By registering <b>you accept that your identity string and 
                 the information you enter below will be visible to other 
                 authenticated users and client-services of GOCDB</b>, including  
                 those authenticated 
                 by the <a href="https://www.igtf.net">Interoperable Global Trust Federation (IGTF)</a> 
                 and <a href="http://www.ukfederation.org.uk/">UK Access Management Federation</a>.</li>   
               <li>This data is re-published by GOCDB and used by EGI for 
                 Monitoring, Accounting and used in its data processing systems.</li> 
               <li>If you do not provide this consent, please do <b>NOT</b> register.</li>
            </ul>
        </div>


        <div class="listContainer">
            <b>Authentication Attributes:</b>
            <br>
            <?php
            foreach ($params['authAttributes'] as $key => $val) {
                $attributeValStr = '';
                foreach ($val as $v) {
                    $attributeValStr .= $v . ' ,';
                }
                if(strlen($attributeValStr) > 2){$attributeValStr = substr($attributeValStr, 2);}
                xecho('[' . $key . ']  [' . $attributeValStr . ']');
                echo '<br>';
            }
            ?>
        </div> 
        <br>

        <span class="input_name">
            Title
        </span>

        <select name="TITLE" class="add_edit_form">
            <option value="Mr">Mr</option>
            <option value="Mrs">Mrs</option>
            <option value="Miss">Miss</option>
            <option value="Ms">Ms</option>
            <option value="Prof">Prof</option>
            <option value="Dr">Dr</option>
        </select>

        <span class="input_name">
            Forename *
            <span class="input_syntax" >(unaccentuated letters, spaces, dashes and quotes)</span>
        </span>
        <input class="input_input_text" type="text" name="FORENAME" />

        <span class="input_name">
            Surname *
            <span class="input_syntax" >(unaccentuated letters, spaces, dashes and quotes)</span>
        </span>
        <input class="input_input_text" type="text" name="SURNAME" />

        <span class="input_name">
            E-Mail *
            <span class="input_syntax" >(valid e-mail format)</span>
        </span>
        <input class="input_input_text" type="text" name="EMAIL" />

        <span class="input_name">
            Telephone Number
            <span class="input_syntax" >(numbers, optional +, dots spaces or dashes)</span>
        </span>
        <input class="input_input_text" type="text" name="TELEPHONE" />

        <br />
    	<input class="input_button" type="submit" value="Submit" />
    </form>
</div>