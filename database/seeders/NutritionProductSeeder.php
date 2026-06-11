<?php

namespace Database\Seeders;

use App\Enums\Nutrition\ProductStatus;
use App\Models\Nutrition\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NutritionProductSeeder extends Seeder
{
    private const AUTHOR_UUID = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';

    public function run(): void
    {
        $this->ensureAuthorExists();

        $products = [
            ...$this->productsForStatus(ProductStatus::Draft, [
                ['name' => 'Молоко «Ёрмолино» 2.5%', 'description' => 'Питьевое пастеризованное', 'proteins' => 2.9, 'fats' => 2.5, 'carbs' => 4.7, 'calories' => 53],
                ['name' => 'Хлеб «Дарницкий»', 'description' => 'Ржано-пшеничный', 'proteins' => 6.8, 'fats' => 1.3, 'carbs' => 40.9, 'calories' => 203],
                ['name' => 'Гречка на воде', 'description' => 'Каша без масла', 'proteins' => 4.2, 'fats' => 0.6, 'carbs' => 19.9, 'calories' => 101],
                ['name' => 'Творог 5%', 'description' => 'Зернистый', 'proteins' => 17.2, 'fats' => 5.0, 'carbs' => 1.8, 'calories' => 121],
                ['name' => 'Кефир 1%', 'description' => 'Биойогурт', 'proteins' => 3.0, 'fats' => 1.0, 'carbs' => 4.0, 'calories' => 40],
                ['name' => 'Сметана 15%', 'description' => 'Домашняя', 'proteins' => 2.6, 'fats' => 15.0, 'carbs' => 3.2, 'calories' => 158],
                ['name' => 'Банан', 'description' => 'Свежий, средний', 'proteins' => 1.5, 'fats' => 0.2, 'carbs' => 21.8, 'calories' => 96],
                ['name' => 'Овсянка на молоке', 'description' => 'Каша 2.5%', 'proteins' => 4.5, 'fats' => 3.2, 'carbs' => 16.8, 'calories' => 118],
                ['name' => 'Сыр «Российский»', 'description' => 'Твёрдый, 50%', 'proteins' => 23.0, 'fats' => 30.0, 'carbs' => 0.0, 'calories' => 364],
                ['name' => 'Йогурт «Активия» натуральный', 'description' => 'Без добавок', 'proteins' => 4.5, 'fats' => 3.2, 'carbs' => 6.0, 'calories' => 75],
                ['name' => 'Говядина тушёная', 'description' => 'Без масла', 'proteins' => 25.0, 'fats' => 12.5, 'carbs' => 0.0, 'calories' => 217],
                ['name' => 'Картофель отварной', 'description' => 'В мундире', 'proteins' => 2.0, 'fats' => 0.1, 'carbs' => 16.7, 'calories' => 77],
                ['name' => 'Макароны отварные', 'description' => 'Из твёрдых сортов', 'proteins' => 5.0, 'fats' => 0.9, 'carbs' => 30.0, 'calories' => 149],
                ['name' => 'Лосось запечённый', 'description' => 'Филе без кожи', 'proteins' => 22.5, 'fats' => 12.0, 'carbs' => 0.0, 'calories' => 197],
                ['name' => 'Апельсин', 'description' => 'Свежий', 'proteins' => 0.9, 'fats' => 0.2, 'carbs' => 8.1, 'calories' => 43],
                ['name' => 'Помидор свежий', 'description' => 'Средний', 'proteins' => 0.9, 'fats' => 0.2, 'carbs' => 3.9, 'calories' => 23],
                ['name' => 'Морковь сырая', 'description' => 'Натёртая', 'proteins' => 0.9, 'fats' => 0.2, 'carbs' => 6.9, 'calories' => 35],
                ['name' => 'Капуста белокочанная', 'description' => 'Свежая', 'proteins' => 1.8, 'fats' => 0.1, 'carbs' => 4.7, 'calories' => 27],
                ['name' => 'Индейка отварная', 'description' => 'Грудка', 'proteins' => 30.1, 'fats' => 0.7, 'carbs' => 0.0, 'calories' => 130],
                ['name' => 'Творожная запеканка', 'description' => 'Домашняя, без сахара', 'proteins' => 14.0, 'fats' => 4.5, 'carbs' => 12.0, 'calories' => 150],
                ['name' => 'Греческий йогурт 2%', 'description' => 'Натуральный', 'proteins' => 9.0, 'fats' => 2.0, 'carbs' => 3.6, 'calories' => 73],
                ['name' => 'Авокадо', 'description' => 'Спелый', 'proteins' => 2.0, 'fats' => 14.7, 'carbs' => 1.8, 'calories' => 160],
                ['name' => 'Миндаль', 'description' => 'Сырой', 'proteins' => 21.2, 'fats' => 49.4, 'carbs' => 9.5, 'calories' => 575],
                ['name' => 'Овсяное печенье', 'description' => 'Домашнее', 'proteins' => 6.5, 'fats' => 14.0, 'carbs' => 58.0, 'calories' => 380],
                ['name' => 'Свинина нежирная', 'description' => 'Запечённая', 'proteins' => 27.0, 'fats' => 8.0, 'carbs' => 0.0, 'calories' => 180],
            ]),
            ...$this->productsForStatus(ProductStatus::Active, [
                ['name' => 'Яблоко', 'description' => 'Свежее, среднее', 'proteins' => 0.4, 'fats' => 0.4, 'carbs' => 11.8, 'calories' => 52],
                ['name' => 'Куриная грудка', 'description' => 'Отварная', 'proteins' => 29.8, 'fats' => 1.8, 'carbs' => 0.0, 'calories' => 137],
                ['name' => 'Рис отварной', 'description' => 'Белый', 'proteins' => 2.4, 'fats' => 0.2, 'carbs' => 24.9, 'calories' => 116],
                ['name' => 'Яйцо куриное', 'description' => 'Вкрутую', 'proteins' => 12.7, 'fats' => 10.9, 'carbs' => 0.7, 'calories' => 155],
                ['name' => 'Огурец свежий', 'description' => 'С кожурой', 'proteins' => 0.8, 'fats' => 0.1, 'carbs' => 2.8, 'calories' => 15],
            ]),
            ...$this->productsForStatus(ProductStatus::Decline, [
                ['name' => 'Колбаса докторская', 'description' => 'Дублирует существующий продукт', 'proteins' => 12.8, 'fats' => 22.2, 'carbs' => 1.5, 'calories' => 257],
                ['name' => 'Чипсы картофельные', 'description' => 'Нет точного состава', 'proteins' => 6.5, 'fats' => 33.0, 'carbs' => 53.0, 'calories' => 536],
                ['name' => 'Энергетик', 'description' => 'Не продукт питания', 'proteins' => 0.0, 'fats' => 0.0, 'carbs' => 11.0, 'calories' => 45],
                ['name' => 'Суп быстрого приготовления', 'description' => 'Слишком общее название', 'proteins' => 4.0, 'fats' => 15.0, 'carbs' => 60.0, 'calories' => 400],
                ['name' => 'Салат Цезарь', 'description' => 'Без указания рецепта', 'proteins' => 8.0, 'fats' => 12.0, 'carbs' => 6.0, 'calories' => 170],
            ]),
        ];

        foreach ($products as $product) {
            Product::query()->firstOrCreate(
                ['name' => $product['name'], 'status' => $product['status']],
                $product,
            );
        }
    }

    private function ensureAuthorExists(): void
    {
        if (DB::table('users')->where('uuid', self::AUTHOR_UUID)->exists()) {
            return;
        }

        DB::table('users')->insert([
            'uuid' => self::AUTHOR_UUID,
            'name' => 'seed_user',
            'email' => 'seed@temp.local',
            'password' => bcrypt(Str::random(32)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<array{name: string, description: string, proteins: float, fats: float, carbs: float, calories: float}>  $items
     * @return list<array{name: string, description: string, proteins: float, fats: float, carbs: float, calories: float, author_uuid: string, status: ProductStatus}>
     */
    private function productsForStatus(ProductStatus $status, array $items): array
    {
        return array_map(fn (array $item): array => [
            ...$item,
            'author_uuid' => self::AUTHOR_UUID,
            'status' => $status,
        ], $items);
    }
}
