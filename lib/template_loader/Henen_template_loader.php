<?php
    namespace henen_template;

    final class Loader {
        public $show_errors = false;
        public $show_warnings = true;
        private $template;
        private $file_type = ".tpl";
        private $max_template_size = 1048576;
        private $error;
        private $template_is_rendered = false;
        private $current_dimension = 0;
        private $max_dimension = 5;
        private $max_includes;
        private $template_name;
        private $max_cache_length = 200;
        private $max_cache_hours;
        private $templates_folder;
        private $allow_template_caching; 
        private $loading_from_cache = false; 

        function __construct(
            String $template_name, 
            String $templates_folder = null,
            int $max_single_template_includes = null,
            array $template_caching = ['allow' => false, 'max_store_hours' => 24])
        {
            (is_null($templates_folder))? $this->templates_folder = $_SERVER["DOCUMENT_ROOT"] . '/tmpl/' : $this->templates_folder = $templates_folder;
            if(!file_exists($this->templates_folder)) mkdir($this->templates_folder);
            $this->template_name = $template_name;
            $get_file = $this->templates_folder . $template_name . $this->file_type;


            $this->allow_template_caching = $template_caching['allow'] ?? false;
            $this->max_cache_hours = $template_caching['max_store_hours'] ?? 24;
            $this->max_includes = $max_single_template_includes ?? 10;

            if($this->allow_template_caching) {
                $template_from_cached = self::load_from_cache();
                if($template_from_cached != null) { 
                    $this->loading_from_cache = true;
                    $this->template = $template_from_cached;
                }
            }


            if(file_exists($get_file) && $this->loading_from_cache === false) {
                if(
                    filesize($get_file) !== false &&
                    filesize($get_file) > $this->max_template_size
                ) {
                    $this->error = "Template \"" . $template_name . "\" is too big, max file size is 1MB";
                } else {
                    $this->template = file_get_contents($get_file);
                    self::include_template();
                }
            } else {
                $this->error = "Template \"" . $template_name . "\" does not exist";
            }

        }

        private function starts_with(String $string, String $startString) {
            $len = strlen($startString);
            return (substr($string, 0, $len) === $startString);
        }
        private function ends_with(String $string, String $endString) {
            $len = strlen($endString);
            if($len == 0) return true;
            return (substr($string, -$len) === $endString);
        }

        private function include_template() {
            if($this->loading_from_cache === true) return;

            $check_for_includes = preg_match_all('/\<\!\-\-include\[[^\[\]\"\']+\]\-\-\>/', $this->template, $result);
            if($check_for_includes > $this->max_includes){
                die("<p>Can not include more than ". $this->max_includes ." templates in a single template</p>");
            } elseif($check_for_includes) {
                $includes = $result[0];
                $num_of_includes = count($includes);
                for($i = 0; $i < $num_of_includes; $i++) {
                    $get_include = $includes[$i];
                    if(empty($get_include)) { continue; }
                    $get_template_name = str_replace('<!--include[', '', $get_include);
                    $get_template_name = str_replace(']-->', '', $get_template_name);
                    $get_file = $this->templates_folder . $get_template_name . $this->file_type;
                    if(file_exists($get_file)) {

                        if(
                            filesize($get_file) !== false &&
                            filesize($get_file) > $this->max_template_size
                        ) {
                            $msg = "<p>Could not include template \"" . $get_template_name . "\", because file size is more than 1MB</p>";
                            $this->template = str_replace($get_include, $msg, $this->template);
                        } else {
                            $this->template = str_replace($get_include, file_get_contents($get_file), $this->template);
                            if($this->current_dimension < $this->max_dimension) {
                                $this->current_dimension += 1;
                                self::include_template();
                            }
                        }

                    } else {
                        if($this->show_warnings) {
                            $msg = "<p>Could not include template \"" . $get_template_name . "\", because it does not exist</p>";
                            $this->template = str_replace($get_include, $msg, $this->template);
                        }
                    }
                }
            }

        }

        private function cache() {

            $cache_dir = $this->templates_folder . "cache/";
            $cache_obj_info = $cache_dir . '0.cobj';
            if(!file_exists($cache_dir)) {
                if(!mkdir($cache_dir, 0755)){
                    $this->error = "Unable to cache templates, please check your drive writing permission";
                }
            }
            if(file_exists($cache_dir)) {
                if(!file_exists($cache_obj_info)) {
                    fopen($cache_obj_info, 'w');
                }
                if(filesize($cache_obj_info) > $this->max_template_size) {
                    @unlink($cache_obj_info);
                } else {
                    $cache_obj_info_string = file_get_contents($cache_obj_info);

                    $templates_info = array(
                        array(
                            "template_name" => $this->template_name,
                            "mod_time" => time(),
                            "template" => $this->template
                            )
                    );

                    if(!empty($cache_obj_info_string)) {
                        $get_cached_obj_info = json_decode($cache_obj_info_string, true);
                        if(is_array($get_cached_obj_info)){
                            if(count($get_cached_obj_info) > $this->max_cache_length) {
                                @unlink($cache_obj_info);
                            } else {
                                for($i = 0; $i < count($get_cached_obj_info); $i++){
                                    $value = $get_cached_obj_info[$i];
                                    if(in_array($this->template_name, $value)) {
                                        $get_cached_obj_info = array_splice($get_cached_obj_info, $i, 1);
                                    }
                                }
                                $get_cached_obj_info = array_merge($get_cached_obj_info, $templates_info);
                                file_put_contents($cache_obj_info, json_encode($get_cached_obj_info));
                            }
                        } else {
                            @unlink($cache_obj_info);
                        }
                    } else {
                        file_put_contents($cache_obj_info, json_encode($templates_info));
                    }

                }
            }

        }

        private function load_from_cache() {

            $cache_dir = $this->templates_folder . "cache/";
            $cache_obj_info = $cache_dir . '0.cobj';

            if(file_exists($cache_dir)) {
                if(file_exists($cache_obj_info)) {
                    
                    if(filesize($cache_obj_info) <= $this->max_template_size) {
                        
                        $cache_obj_info_string = file_get_contents($cache_obj_info);
                        
                        if(!empty($cache_obj_info_string)) {
                            $get_cached_obj_info = json_decode($cache_obj_info_string, true);
                            if(is_array($get_cached_obj_info)){
                                $get_cached_obj_info_length = count($get_cached_obj_info);
                                if($get_cached_obj_info_length <= $this->max_cache_length) {
                                    
                                    foreach($get_cached_obj_info as $array) {
                                        $x = $array["template_name"];
                                        if($x == $this->template_name) {
                                            $current_time = time();
                                            $last_mod_time = $array["mod_time"];
                                            $hours = round(abs($current_time - $last_mod_time) / 3600);
                                            if($hours <= $this->max_cache_hours) {
                                                return $array["template"];
                                            }
                                        }
                                    }  
                                }
                            }
                        }
                    }
                }
            }
            return null;
        }

        final public function forEach(String $foreach_name, array | Object $parameters) {
            if($this->loading_from_cache === true) return;
            
            $parameters = (array) $parameters;

            if(preg_match( 
                "/<!\-\-forEach\[$foreach_name\]\-\->[\s\S]+<!\-\-end\[$foreach_name\]\-\->/",
                $this->template,
                $result
            )) {

                $all = "";
                $length = count($parameters);
                $get_result = $result[0];
                $tmp = "";
                $i = 0;

                if(count(array_filter($parameters, 'is_array')) > 0) {

                    for($i = 0; $i < $length; $i++) {
                        $tmp = "";
                        $get_index = $parameters[$i];
                        $keys = array_keys($get_index);
                        foreach($keys as $key) {
    
                            $value = $get_index[$key];
    
                            if(!preg_match("/[^%i01]/", $value) && strlen($value) == 3) {
    
                                ($value == "%i1")? $mk_i = $i + 1 : $mk_i = $i;
                                (empty($tmp))? $tmp = str_replace("<!--{" . $key . "}-->", $mk_i, $get_result) : $tmp = str_replace("<!--{" . $key . "}-->", $mk_i, $tmp);
                                
                            }
    
                            (empty($tmp))? $tmp = str_replace("<!--{" . $key . "}-->", htmlentities($value), $get_result) : $tmp = str_replace("<!--{" . $key . "}-->", htmlentities($value), $tmp);
    
                        }
                        $all .= $tmp;
                        
                    }

                } else {

                    $tmp = "";
                    $get_index = $parameters;
                    $keys = array_keys($get_index);
                    foreach($keys as $key) {

                        $value = $get_index[$key];

                        if(!preg_match("/[^%i01]/", $value) && strlen($value) == 3) {

                            ($value == "%i1")? $mk_i = $i + 1 : $mk_i = $i;
                            (empty($tmp))? $tmp = str_replace("<!--{" . $key . "}-->", $mk_i, $get_result) : $tmp = str_replace("<!--{" . $key . "}-->", $mk_i, $tmp);
                            
                        }

                        (empty($tmp))?
                        $tmp = str_replace("<!--{" . $key . "}-->", htmlentities($value), $get_result)
                        :
                        $tmp = str_replace("<!--{" . $key . "}-->", htmlentities($value), $tmp);
        

                    }
                    $all .= $tmp;

                }

                $this->template = str_replace($get_result, $all, $this->template);

            }
        }

        final public function for(String $for_name, array $parameters) {
            if($this->loading_from_cache === true) return;
            if(preg_match( 
                "/<!\-\-for\[$for_name\]\-\->[\s\S]+<!\-\-end\[$for_name\]\-\->/",
                $this->template,
                $result
            )) {

                $all = "";
                $length = count($parameters);
                $get_result = $result[0];                
                $tmp = "";

                for($i = 0; $i < $length; $i++) {

                    $value = $parameters[$i];
                    (empty($tmp))? 
                    $tmp = str_replace("<!--{value}-->", htmlentities($value), $get_result) 
                    : 
                    $tmp .= str_replace("<!--{value}-->", htmlentities($value), $get_result);

                }
                $all .= $tmp;

                $this->template = str_replace($get_result, $all, $this->template);

            }
        }


        final public function set(String $parameter_name, String $value) {
            if($this->loading_from_cache === true) return;
            if(empty($parameter_name)) return;
            if($this->template_is_rendered && $this->show_errors) die("You can't set parameter(s) after rendering template");
            if(!preg_match("/^[a-zA-Z0-9\.]*$/", $parameter_name)) { 
                die("Template parameters can only contain letters, numbers and dots"); 
            } else {
                
                if(
                    self::starts_with($parameter_name, ".") || self::ends_with($parameter_name, ".")
                ) { 
                    die("Template parameters can not start or end with a dot"); 
                }
                if(preg_match("/\.{2,}/", $parameter_name)) { 
                    die("Invalid template parameter \"" . 
                    htmlspecialchars($parameter_name) . "\", expected \"" .
                    preg_replace('/\.{2,}/', ".", htmlspecialchars($parameter_name)) . "\" not \"" .
                    htmlspecialchars($parameter_name) ."\""); 
                }
                $this->template = str_replace("<!--[" . $parameter_name . "]-->", htmlentities($value), $this->template);
                
            }
        }

        final public function render($render_comments = true) {
            if($this->show_errors) echo $this->error;
            if($render_comments === false) $this->template = preg_replace("~<!--(.*?)-->~s", "", $this->template);
            if($this->allow_template_caching && $this->loading_from_cache == false) self::cache();
            echo $this->template;
            $this->template_is_rendered = true;
        }
    }