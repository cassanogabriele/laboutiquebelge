<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Product;
use App\Entity\Category;
use Faker;
use Cocur\Slugify\Slugify;

class AppFixtures extends Fixture
{
    
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');
    
        $categories = $manager->getRepository(Category::class)->findAll();

        foreach ($categories as $category) {
            for ($i = 0; $i < 10; $i++) {
                $randomCategory = $categories[array_rand($categories)];
                $product = (new Product())                    
                    ->setCategory($randomCategory)
                    ->setName($faker->name)
                    ->setSlug($faker->slug)
                    ->setIllustration($faker->imageUrl($width = 640, $height = 480))
                    ->setSubtitle($faker->name)
                    ->setDescription($faker->name)
                    ->setPrice($faker->randomFloat($nbMaxDecimals = 2, $min = 0, $max = 1000));

                $manager->persist($product);
            }
        }

        $manager->flush();
    }
}
