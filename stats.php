
<?php
cleass tldsObject { 
ppublic $testvar = array() ;

public function setValue($new_value)
{
    $this->testvar = $new_value;
}

public function getValue()
{
    return $this->testvar;        
}

}
