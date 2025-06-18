<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{

    public function load(ObjectManager $manager): void
    {
        $fullDescription = "Déodorant Nécessaire, une formule révolutionnaire composée exclusivement d'ingrédients naturels pour une protection efficace et bienfaisante. 

Chaque flacon de 50 ml renferme le secret d'une fraîcheur longue durée, sans compromettre votre bien-être ni l'environnement. Conçu avec soin, ce déodorant allie le pouvoir antibactérien des extraits de plantes aux vertus apaisantes des huiles essentielles, assurant une sensation de confort toute la journée. 

Grâce à sa formule non irritante et respectueuse de votre peau, Nécessaire offre une alternative saine aux déodorants conventionnels, tout en préservant l'équilibre naturel de votre corps.";

        $products = [
            [
                "name" => "Kit d'hygiène recyclable",
                "short" => "Pour une salle de bain éco-friendly",
                "full" => $fullDescription,
                "price" => 24.99,
                "picture" => "kit-hygiene-recyclable.jpg"
            ],
            [
                "name" => "Shot Tropical",
                "short" => "Fruits frais, pressés à froid",
                "full" => $fullDescription,
                "price" => 4.50,
                "picture" => "shot-tropical.jpg"
            ],
            [
                "name" => "Gourde en bois",
                "short" => "50cl, bois d'olivier",
                "full" => $fullDescription,
                "price" => 16.90,
                "picture" => "gourde-bois.jpg"
            ],
            [
                "name" => "Disques Démaquillants x3",
                "short" => "Solution efficace pour vous démaquillez en douceur",
                "full" => $fullDescription,
                "price" => 19.90,
                "picture" => "disques-demaquillant.jpg"
            ],
            [
                "name" => "Bougie Lavande & Patchouli",
                "short" => "Cire naturelle",
                "full" => $fullDescription,
                "price" => 32,
                "picture" => "bougie-lavande-patchouli.jpg"
            ],
            [
                "name" => "Brosse à dent",
                "short" => "Bois de hêtre rouge issu de forêts gérées durablement",
                "full" => $fullDescription,
                "price" => 5.40,
                "picture" => "brosse-dent.jpg"
            ],
            [
                "name" => "Kit couvert en bois",
                "short" => "Revêtement Bio en olivier & sac de transport",
                "full" => $fullDescription,
                "price" => 12.30,
                "picture" => "kit-couverts-bois.jpg"
            ],
            [
                "name" => "Nécessaire, déodorant Bio",
                "short" => "50ml déodorant à l'eucalyptus",
                "full" => $fullDescription,
                "price" => 8.50,
                "picture" => "deodorant-bio.jpg"
            ],
            [
                "name" => "Savon Bio",
                "short" => "Thé, Orange & Girofle",
                "full" => $fullDescription,
                "price" => 18.90,
                "picture" => "savon-bio.jpg"
            ]
        ];

        foreach ($products as $data) {
            $product = (new Product())
                ->setName($data['name'])
                ->setShortDescription($data['short'])
                ->setFullDescription($data['full'])
                ->setPrice($data['price'])
                ->setPicture($data['picture']);

            $manager->persist($product);
        }
        $manager->flush();
    }
}
