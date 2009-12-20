<?php

class Sphinx_Search_Index
{
    protected $values = array(
        'docinfo'               => 'extern',
        'mlock'                 =>  0,
        'min_stemming_len'      =>  4,
        // Min word length indexed ex 4 would not index 'the' but 'they' will be.
        'min_word_len'          =>  1,
        'morphology'            =>  'stem_en',
        // options are sbcs, utf-8; Defaults to sbcs but we want utf-8 default
        'charset_type'         =>  'utf-8',
        // Star searching ex: "abc*"
        'enable_star'           =>  0,
        // Stip html markup
        'html_strip'            => 1,
        //Keep these attr values in the index
        'html_index_attrs'      =>  'img=alt,title; a=title;',
        // Removes contents of these tags when striping html
        'html_remove_elements'  =>  'style, script',
        // index exact words along with morphed
        'index_exact_words'     =>  1,
    );

    public function __set($var, $value)
    {
        $this->values[$var] = $value;
    }

    public function __get($var = NULL)
    {
        if ($var=='values')
        {
            return $this->values;
        }
        return isset($this->values[$var])? $this->values[$var] : NULL;
    }

}
