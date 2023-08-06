<?php

namespace App\EventSubscriber;

use App\Entity\Product;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;

class EasyAdminSubscriber implements EventSubscriberInterface
{
  
   private $appKernel;

   public function __construct(KernelInterface $appKernel){
        $this->appKernel = $appKernel;
   }
   
    public static function getSubscribedEvents() {
        return [
            BeforeEntityPersistedEvent::class => ['setIllustration'],
            BeforeEntityUpdatedEvent::class => ['updateIllustration'],
        ];
    }   
    
    public function uploadIllustration($event){
        $entity = $event->getEntityInstance();
        
        $illustrationFile = isset($_FILES['Product']['tmp_name']['illustration']);
        
        if (!$illustrationFile instanceof UploadedFile) {
            return;
        }

        $tmpName = $illustrationFile->getPathname();
        $filename = uniqid();
        $extension = $illustrationFile->getClientOriginalExtension(); 
        $projectDir = $this->appKernel->getProjectDir();

        move_uploaded_file($tmpName, $projectDir.'/public/uploads/'.$filename.'.'.$extension);

        $entity->setIllustration($filename.'.'.$extension);
    }
    
    public function updateIllustration(BeforeEntityUpdatedEvent $event)
    {           
        $illustrationFile = isset($_FILES['Product']['tmp_name']['illustration']);
       
        if(!($event->getEntityInstance() instanceof Product)){
            if ($illustrationFile && $_FILES['Product']['tmp_name']['illustration'] != '') {
                $this->uploadIllustration($event);
            }        
        }   
    }
    
    public function setIllustration(BeforeEntityPersistedEvent $event)
    {
        if(!($event->getEntityInstance() instanceof Product)){
            $this->uploadIllustration($event); 
        }   
    }

}
