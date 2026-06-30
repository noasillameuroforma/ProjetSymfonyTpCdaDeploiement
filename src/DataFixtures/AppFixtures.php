<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $em): void
    {
        // 1) Catégories
        $catNames = ['T-shirts', 'Chaussures', 'Accessoires'];
        $categories = [];

        foreach ($catNames as $name) {
            $cat = (new Category())
                ->setName($name)
                ->setSlug($this->slugify($name));

            $em->persist($cat);
            $categories[] = $cat;
        }

        // 2) Produits
        $products = [];

        for ($i = 1; $i <= 10; $i++) {
            $prod = (new Product())
                ->setName("Produit $i")
                ->setSlug("produit-$i")
                ->setPrice(random_int(500, 5000)) // 5€ à 50€ si tu stockes en centimes
                ->setDescription("Description du produit $i")
                ->setCategory($categories[array_rand($categories)]);

            $em->persist($prod);
            $products[] = $prod;
        }

        // 3) Utilisateur
        $user = (new User())
            ->setEmail('user@example.com')
            ->setFullName('Alice Martin')
            ->setRoles(['ROLE_USER']);

        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $em->persist($user);

        // 4) Commande + items
        $order = (new Order())
            ->setUser($user)
            ->setStatus('paid')
            ->setCreatedAt(new \DateTimeImmutable());

        $em->persist($order);

        // Sécurité : si jamais tu changes le code plus tard
        if (count($products) === 0) {
            throw new \RuntimeException('Aucun produit n’a été généré dans les fixtures.');
        }

        $total = 0;

        for ($k = 0; $k < 2; $k++) {
            $prod = $products[array_rand($products)];
            $qty  = random_int(1, 3);

            $unitPrice = $prod->getPrice();
            $lineTotal = $qty * $unitPrice;

            $item = (new OrderItem())
                ->setOrderId($order)
                ->setProduct($prod)
                ->setQuantity($qty)
                ->setUnitPrice($unitPrice)
                ->setLineTotal($lineTotal);

            $em->persist($item);
            $total += $lineTotal;
        }

        $order->setTotal($total);

        // 5) Flush final
        $em->flush();
    }

    private function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('~[^\pL\d]+~u', '-', $value);
        $value = trim($value, '-');
        return $value ?: 'n-a';
    }
}
