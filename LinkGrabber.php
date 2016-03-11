<?php

/*
*	@Project	: JaniSec Vulnerability Searcher Engine
*	@Class		: LinkGrabber
*	@Create on	: 10.02.2016
*	@Coder		: Burtay
*	@Contact	: admin@burtay.org
*	@Twitter	: @haciburtay
*	@Blog		: www.burtay.org
*/


class atac extends Threaded
{
    public function run()
    {}
}

class LinkGrabber
{

    public $internal_links;
    public $external_links;
    private $visited;
    private $toplanan = array();
    private $params = array();
    private $_threadSayisi = 10;
    private $_derinlik = 4;
    
    
    public function __construct($params = array())
    {
        $this->internal_links = new atac();
        $this->external_links = new atac();
        $this->visited = new atac();
        $this->toplanan = new atac();
        $this->params = $params;
        $this->linkleri_cıkar();
        
   }      
    public function linkleri_cıkar()
    {
        $this->toplanan[0] =array();
        $a = new LinkGrabberThread($this->params[0],$this->params[1],$this->internal_links,$this->external_links,$this->visited,$this->toplanan[0],0);
        $a->run();
        echo "Anasayfa Taraması Bitti\n";
        //print_r($this->toplanan[0]); 
        for ($i=1;$i<=$this->_derinlik;$i++)
        {                                                 
            $this->toplanan[$i] = array();
            $pool = new Pool($this->_threadSayisi);
            $is_listesi = array();
            
            foreach($this->toplanan[$i-1] as $path)
            {                
                $is_listesi[] = new LinkGrabberThread($this->params[0],$path,$this->internal_links,$this->external_links,$this->visited,$this->toplanan[$i],$i);
            }
            foreach($is_listesi as $is)
            {
                $pool->submit($is);
            }
            $pool->shutdown();                           
            //print_r($this->toplanan[$i]);
            echo $i.". Derinlik Taraması Bitti\n";
        }
       echo "Tarama Tamamlandı\n"; 
       print_r(array_unique(get_object_vars($this->internal_links)));
       print_r(array_unique(get_object_vars($this->external_links)));
       
    }
}

class LinkGrabberThread extends Worker
{
    private $domain;
    private $path;
    private $internal_links;
    private $external_links;
    private $visited ;
    private $toplanan;
    private $derinlik;
    
    public function __construct($domain,$path,$internal_links,$external_links,$visited,$toplanan,$derinlik) 
    {
        $this->domain = $domain;
        $this->path = $path;
        $this->internal_links = $internal_links;
        $this->external_links = $external_links;
        $this->visited = $visited;
        $this->toplanan = $toplanan;
        $this->derinlik = $derinlik;        
    }
    
    public function run()
    {
        if(!$this->visited["$this->domain.'/'.$this->path"])
        {
            echo 'http://www.'.$this->domain.'/'.$this->path." Alınıyor\n";
            $kaynak = curl::get('http://www.'.$this->domain.'/'.$this->path);     
//            echo $kaynak;
            $pattern = '#href="(.*?)"#si';
            preg_match_all($pattern, $kaynak,$linkler);
            foreach($linkler[1] as $link)
            {			
                $this->LinkAyir($link,$this->params[0],$this->toplanan,$this->derinlik);
            }
        }
        else 
        {
            echo $this->path." Daha önce Tarandı.\n";
        }
        $this->visited["$this->domain.'/'.$this->path"] = true;
    }
    
    private function LinkAyir($link,$domain,$derinlik)
    {
        //unset(LinkGrabber::$kepce);      
        if(preg_match('/http/',$link) or preg_match('/https/',$link))
        {
            // http ve https sil
            $eski = ['#http://#','#https://#'];
            $yeni = ['',''];
            $link = preg_replace($eski, $yeni, $link);

			

            // www. sil
            $link = preg_replace('#www\.#','',$link);


            // / sonrasını sil
            $ayir = explode('/',$link);
            if($ayir[0] == $domain)
            {
                $path = preg_replace('#'.$domain.'#','',$link);
                $path = preg_replace('#/#','',$path);
                //$path = preg_replace('/#/','',$path);
                $this->internal_links[] = $path;
                //$d = get_object_vars($this->toplanan[$i-1]);
                $this->toplanan[] = $path;
            }
            else
            {
                $this->external_links[]  = $ayir[0];
            }
        }
        else
        {
            //echo $link." Eklendi \n";
            //$link = preg_replace('/#/','', $link);
            $this->internal_links[] = $link;
            $this->toplanan[] = $link;
        }    
        
    }
}