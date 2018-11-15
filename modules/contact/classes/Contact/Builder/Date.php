<?php
/**
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Contact_Builder_Date extends Contact_Builder_Base {
    /**
     *
     * @var string
     */
    protected $contact_class = "zesk\\Contact_Date";
    
    /**
     *
     * {@inheritDoc}
     * @see \zesk\Contact_Builder_Base::process()
     */
    public function process(Contact_Import $import, $key, $value) {
        if (!is_date($value)) {
            throw new Exception_Syntax(__("Not a valid date."));
        }
        $empty_values = $import->empty_date_values();
        if (in_array($value, $empty_values)) {
            return;
        }
        
        $data = array(
            'value' => $value,
        ) + $this->data;
        $map = array(
            'key' => $key,
        );
        $data = map($data, $map);
        
        $import->merge_item($this->contact_class, $data['value'], $data);
        return true;
    }
}
