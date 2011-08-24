<?php
/**
 * Validator.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010 Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Validator
 */
class Validator
{

    protected $request = false;

    protected $validators = array();

    protected $filters = array();

    protected $errors = array();

    public function __construct()
    {
        $this->request = Application::getInstance()->getRequest();
    }

    public function addValidator( array $validator )
    {
        if ( !isset( $validator['field'] ) ) {
            throw new LibraryException( "The validator array must contain a form field name with key 'field'", E_USER_ERROR );
        }
        $this->validators[] = $validator;
        return $this;
    }

    public function setValidators( array $validators )
    {
        foreach ( $validators as $validator ) {
            if ( !isset( $validator['field'] ) ) {
                throw new LibraryException( "Each validator array in the validators array must contain a form field name with key 'field'", E_USER_ERROR );
            }
        }
        $this->validators = $validators;
        return $this;
    }

    public function addFilter( array $filter )
    {
        if ( !isset( $filter['field'] ) ) {
            throw new LibraryException( "The filter array must contain a form field name with key 'field'", E_USER_ERROR );
        }
        $this->filters[] = $filter;
        return $this;
    }

    public function setFilters( array $filters )
    {
        foreach ( $filters as $filter ) {
            if ( !isset( $filter['field'] ) ) {
                throw new LibraryException( "Each filter array in the filters array must contain a form field name with key 'field'", E_USER_ERROR );
            }
        }
        $this->filters = $filters;
        return $this;
    }

    public function isValid()
    {
    }

    public function getErrors()
    {
        return ($this->errors) ? $this->errors : false;
    }

    public function getError( $field )
    {
        if ( $this->errors ) {
            if ( isset( $this->errors[$field] ) ) {
                return $this->errors[$field];
            }
        }
        return false;
    }

    protected function formatFieldName( $name )
    {
        return ucfirst( str_replace( '-', ' ', $name ) );
    }

    protected function getFieldValue($field){
        $method = $this->request->getRequestMethod();
        if ($method == 'GET') {
            return $this->request->getGet($field);
        } elseif ($method  == 'POST')  {
            return $this->request->getPost($field);
        }
    }

    protected function runFilters()
    {
    }

    protected function runValidators()
    {
    }

    protected function validateRequired()
    {

    }

    protected function validateString()
    {

    }

    protected function validateEmail()
    {
    }
}