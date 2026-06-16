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
                ['name' => 'Гречневая крупа (сухая)', 'description' => 'Крупа ядрица', 'proteins' => 12.6, 'fats' => 3.3, 'carbs' => 62.1, 'calories' => 313],
                ['name' => 'Рис белый шлифованный (сухой)', 'description' => 'Круглозерный/длиннозерный усредненно', 'proteins' => 7.0, 'fats' => 0.6, 'carbs' => 74.0, 'calories' => 333],
                ['name' => 'Рис бурый (сухой)', 'description' => 'Нешлифованный', 'proteins' => 7.5, 'fats' => 2.6, 'carbs' => 72.0, 'calories' => 337],
                ['name' => 'Овсяные хлопья (сухие)', 'description' => 'Геркулес, без добавок', 'proteins' => 12.3, 'fats' => 6.1, 'carbs' => 59.5, 'calories' => 342],
                ['name' => 'Пшено (сухое)', 'description' => 'Крупа пшенная', 'proteins' => 11.5, 'fats' => 3.3, 'carbs' => 69.3, 'calories' => 348],
                ['name' => 'Перловая крупа (сухая)', 'description' => 'Ячмень шлифованный', 'proteins' => 9.3, 'fats' => 1.1, 'carbs' => 73.7, 'calories' => 320],
                ['name' => 'Ячневая крупа (сухая)', 'description' => 'Дробленый ячмень', 'proteins' => 10.0, 'fats' => 1.3, 'carbs' => 65.4, 'calories' => 313],
                ['name' => 'Булгур (сухой)', 'description' => 'Пшеница пропаренная', 'proteins' => 12.3, 'fats' => 1.3, 'carbs' => 57.6, 'calories' => 342],
                ['name' => 'Кускус (сухой)', 'description' => 'Пшеничная крупа мелкая', 'proteins' => 12.8, 'fats' => 0.6, 'carbs' => 72.4, 'calories' => 376],
                ['name' => 'Киноа (сухая)', 'description' => 'Крупа киноа', 'proteins' => 14.1, 'fats' => 6.1, 'carbs' => 57.2, 'calories' => 368],
                ['name' => 'Манная крупа (сухая)', 'description' => 'Пшеничная манка', 'proteins' => 10.3, 'fats' => 1.0, 'carbs' => 73.3, 'calories' => 333],
                ['name' => 'Кукурузная крупа (сухая)', 'description' => 'Крупа кукурузная шлифованная', 'proteins' => 8.3, 'fats' => 1.2, 'carbs' => 75.0, 'calories' => 337],
                ['name' => 'Полба (сухая)', 'description' => 'Зерно полбы', 'proteins' => 14.6, 'fats' => 2.4, 'carbs' => 59.5, 'calories' => 337],
                ['name' => 'Чечевица (сухая)', 'description' => 'Красная/зеленая усредненно', 'proteins' => 24.0, 'fats' => 1.5, 'carbs' => 46.3, 'calories' => 295],
                ['name' => 'Горох колотый (сухой)', 'description' => 'Сухой горох', 'proteins' => 20.5, 'fats' => 2.0, 'carbs' => 53.3, 'calories' => 299],
                ['name' => 'Фасоль белая (сухая)', 'description' => 'Сухая фасоль', 'proteins' => 21.0, 'fats' => 1.6, 'carbs' => 47.0, 'calories' => 298],
                ['name' => 'Нут (сухой)', 'description' => 'Турецкий горох', 'proteins' => 19.0, 'fats' => 6.0, 'carbs' => 61.0, 'calories' => 364],
                ['name' => 'Гречневая каша на воде', 'description' => 'Отварная, без масла', 'proteins' => 4.2, 'fats' => 1.1, 'carbs' => 21.3, 'calories' => 110],
                ['name' => 'Рисовая каша на воде', 'description' => 'Рис отварной', 'proteins' => 2.4, 'fats' => 0.2, 'carbs' => 24.9, 'calories' => 116],
                ['name' => 'Овсяная каша на воде', 'description' => 'Хлопья овсяные', 'proteins' => 2.4, 'fats' => 1.4, 'carbs' => 12.0, 'calories' => 71],
                ['name' => 'Пшенная каша на воде', 'description' => 'Пшено отварное', 'proteins' => 3.0, 'fats' => 0.8, 'carbs' => 17.5, 'calories' => 90],
                ['name' => 'Перловая каша на воде', 'description' => 'Перловка отварная', 'proteins' => 2.3, 'fats' => 0.4, 'carbs' => 22.9, 'calories' => 109],
                ['name' => 'Ячневая каша на воде', 'description' => 'Ячневая крупа отварная', 'proteins' => 2.3, 'fats' => 0.3, 'carbs' => 15.7, 'calories' => 76],
                ['name' => 'Булгур на воде', 'description' => 'Булгур отварной', 'proteins' => 3.1, 'fats' => 0.2, 'carbs' => 18.6, 'calories' => 83],
                ['name' => 'Кускус на воде', 'description' => 'Кускус запаренный', 'proteins' => 3.8, 'fats' => 0.2, 'carbs' => 23.2, 'calories' => 112],
                ['name' => 'Киноа на воде', 'description' => 'Киноа вареная', 'proteins' => 4.4, 'fats' => 1.9, 'carbs' => 21.3, 'calories' => 120],
                ['name' => 'Манная каша на воде', 'description' => 'Манка без молока', 'proteins' => 2.5, 'fats' => 0.2, 'carbs' => 16.8, 'calories' => 80],
                ['name' => 'Кукурузная каша на воде', 'description' => 'Полента/мамалыга, без масла', 'proteins' => 2.3, 'fats' => 0.4, 'carbs' => 15.0, 'calories' => 71],
                ['name' => 'Чечевица отварная', 'description' => 'Без масла', 'proteins' => 9.0, 'fats' => 0.4, 'carbs' => 20.1, 'calories' => 116],
                ['name' => 'Горох отварной', 'description' => 'Без масла', 'proteins' => 6.0, 'fats' => 0.6, 'carbs' => 16.0, 'calories' => 84],
                ['name' => 'Фасоль отварная', 'description' => 'Белая/красная усредненно', 'proteins' => 8.7, 'fats' => 0.5, 'carbs' => 21.5, 'calories' => 127],
                ['name' => 'Нут отварной', 'description' => 'Без масла', 'proteins' => 8.9, 'fats' => 2.6, 'carbs' => 27.4, 'calories' => 164],
                ['name' => 'Овсяная каша молочная', 'description' => 'Готовая каша', 'proteins' => 3.2, 'fats' => 3.2, 'carbs' => 15.5, 'calories' => 100],
                ['name' => 'Рисовая каша молочная', 'description' => 'Готовая каша', 'proteins' => 2.9, 'fats' => 3.1, 'carbs' => 16.0, 'calories' => 104],
                ['name' => 'Манная каша молочная', 'description' => 'Готовая каша', 'proteins' => 3.0, 'fats' => 2.9, 'carbs' => 15.3, 'calories' => 98],
                ['name' => 'Пшенная каша молочная', 'description' => 'Готовая каша', 'proteins' => 3.4, 'fats' => 3.0, 'carbs' => 16.6, 'calories' => 110],
                ['name' => 'Гречневая каша молочная', 'description' => 'Готовая каша', 'proteins' => 4.2, 'fats' => 3.6, 'carbs' => 16.8, 'calories' => 118],
                ['name' => 'Кукурузная каша молочная', 'description' => 'Готовая каша', 'proteins' => 3.1, 'fats' => 3.2, 'carbs' => 16.2, 'calories' => 107],
                ['name' => 'Каша 5 злаков молочная', 'description' => 'Готовая каша', 'proteins' => 3.6, 'fats' => 3.4, 'carbs' => 15.4, 'calories' => 105],
                ['name' => 'Тыквенная каша с рисом', 'description' => 'Готовая каша', 'proteins' => 2.1, 'fats' => 2.8, 'carbs' => 14.8, 'calories' => 92],
                ['name' => 'Овсяная каша с бананом', 'description' => 'Готовая каша сладкая', 'proteins' => 2.8, 'fats' => 2.5, 'carbs' => 17.8, 'calories' => 105],
                ['name' => 'Рисовая каша с изюмом', 'description' => 'Готовая каша сладкая', 'proteins' => 2.7, 'fats' => 2.9, 'carbs' => 19.5, 'calories' => 115],
                ['name' => 'Борщ', 'description' => 'Суп на мясном бульоне, усредненно', 'proteins' => 1.7, 'fats' => 2.7, 'carbs' => 6.7, 'calories' => 49],
                ['name' => 'Щи', 'description' => 'С капустой, усредненно', 'proteins' => 1.3, 'fats' => 2.3, 'carbs' => 3.8, 'calories' => 36],
                ['name' => 'Суп куриный с лапшой', 'description' => 'Домашний, усредненно', 'proteins' => 2.0, 'fats' => 1.5, 'carbs' => 4.3, 'calories' => 39],
                ['name' => 'Суп куриный с рисом', 'description' => 'Домашний, усредненно', 'proteins' => 2.1, 'fats' => 1.6, 'carbs' => 4.0, 'calories' => 38],
                ['name' => 'Гороховый суп', 'description' => 'Суп-пюре/классический, усредненно', 'proteins' => 3.5, 'fats' => 2.2, 'carbs' => 8.0, 'calories' => 66],
                ['name' => 'Фасолевый суп', 'description' => 'Суп с фасолью', 'proteins' => 3.4, 'fats' => 2.0, 'carbs' => 7.1, 'calories' => 60],
                ['name' => 'Чечевичный суп', 'description' => 'Суп с чечевицей', 'proteins' => 3.8, 'fats' => 1.9, 'carbs' => 7.5, 'calories' => 61],
                ['name' => 'Рассольник', 'description' => 'С перловкой, усредненно', 'proteins' => 1.4, 'fats' => 2.0, 'carbs' => 5.0, 'calories' => 45],
                ['name' => 'Солянка мясная', 'description' => 'Сборная мясная, усредненно', 'proteins' => 3.8, 'fats' => 3.8, 'carbs' => 2.2, 'calories' => 58],
                ['name' => 'Уха', 'description' => 'Рыбный суп', 'proteins' => 3.2, 'fats' => 1.5, 'carbs' => 2.0, 'calories' => 34],
                ['name' => 'Овощной суп', 'description' => 'Легкий овощной', 'proteins' => 1.1, 'fats' => 1.7, 'carbs' => 4.0, 'calories' => 34],
                ['name' => 'Грибной суп', 'description' => 'С картофелем, усредненно', 'proteins' => 1.6, 'fats' => 2.2, 'carbs' => 3.8, 'calories' => 41],
                ['name' => 'Крем-суп тыквенный', 'description' => 'Суп-пюре, усредненно', 'proteins' => 1.4, 'fats' => 2.8, 'carbs' => 6.2, 'calories' => 58],
                ['name' => 'Крем-суп грибной', 'description' => 'Сливочный, усредненно', 'proteins' => 2.0, 'fats' => 3.3, 'carbs' => 5.0, 'calories' => 62],
                ['name' => 'Свекольник', 'description' => 'Холодный суп, усредненно', 'proteins' => 1.2, 'fats' => 2.0, 'carbs' => 4.6, 'calories' => 41],
                ['name' => 'Окрошка на кефире', 'description' => 'Классическая, усредненно', 'proteins' => 2.1, 'fats' => 3.0, 'carbs' => 4.1, 'calories' => 52],
                ['name' => 'Харчо', 'description' => 'С рисом, усредненно', 'proteins' => 3.1, 'fats' => 3.7, 'carbs' => 6.0, 'calories' => 73],
                ['name' => 'Лагман', 'description' => 'Суп с лапшой, усредненно', 'proteins' => 4.0, 'fats' => 3.5, 'carbs' => 10.0, 'calories' => 89],
                ['name' => 'Куриный бульон', 'description' => 'Прозрачный', 'proteins' => 0.8, 'fats' => 0.5, 'carbs' => 0.3, 'calories' => 10],
                ['name' => 'Говяжий бульон', 'description' => 'Прозрачный', 'proteins' => 1.0, 'fats' => 0.6, 'carbs' => 0.2, 'calories' => 12],
                ['name' => 'Яблоко', 'description' => 'Свежее, среднее', 'proteins' => 0.4, 'fats' => 0.4, 'carbs' => 11.8, 'calories' => 52],
                ['name' => 'Груша', 'description' => 'Свежее, средняя', 'proteins' => 0.4, 'fats' => 0.3, 'carbs' => 10.9, 'calories' => 47],
                ['name' => 'Банан', 'description' => 'Свежий', 'proteins' => 1.5, 'fats' => 0.2, 'carbs' => 21.8, 'calories' => 96],
                ['name' => 'Апельсин', 'description' => 'Свежий', 'proteins' => 0.9, 'fats' => 0.2, 'carbs' => 8.1, 'calories' => 43],
                ['name' => 'Мандарин', 'description' => 'Свежий', 'proteins' => 0.8, 'fats' => 0.2, 'carbs' => 7.5, 'calories' => 38],
                ['name' => 'Лимон', 'description' => 'Свежий', 'proteins' => 0.9, 'fats' => 0.1, 'carbs' => 3.0, 'calories' => 34],
                ['name' => 'Грейпфрут', 'description' => 'Свежий', 'proteins' => 0.7, 'fats' => 0.2, 'carbs' => 6.5, 'calories' => 35],
                ['name' => 'Виноград', 'description' => 'Свежий', 'proteins' => 0.6, 'fats' => 0.2, 'carbs' => 16.8, 'calories' => 72],
                ['name' => 'Киви', 'description' => 'Свежий', 'proteins' => 1.1, 'fats' => 0.5, 'carbs' => 10.3, 'calories' => 61],
                ['name' => 'Ананас', 'description' => 'Свежий', 'proteins' => 0.4, 'fats' => 0.2, 'carbs' => 11.8, 'calories' => 52],
                ['name' => 'Манго', 'description' => 'Свежий', 'proteins' => 0.8, 'fats' => 0.4, 'carbs' => 15.0, 'calories' => 60],
                ['name' => 'Персик', 'description' => 'Свежий', 'proteins' => 0.9, 'fats' => 0.1, 'carbs' => 9.5, 'calories' => 39],
                ['name' => 'Нектарин', 'description' => 'Свежий', 'proteins' => 1.1, 'fats' => 0.3, 'carbs' => 10.5, 'calories' => 44],
                ['name' => 'Абрикос', 'description' => 'Свежий', 'proteins' => 0.9, 'fats' => 0.1, 'carbs' => 9.0, 'calories' => 44],
                ['name' => 'Слива', 'description' => 'Свежая', 'proteins' => 0.8, 'fats' => 0.3, 'carbs' => 9.6, 'calories' => 46],
                ['name' => 'Вишня', 'description' => 'Свежая', 'proteins' => 0.8, 'fats' => 0.5, 'carbs' => 11.3, 'calories' => 52],
                ['name' => 'Черешня', 'description' => 'Свежая', 'proteins' => 1.1, 'fats' => 0.4, 'carbs' => 10.6, 'calories' => 50],
                ['name' => 'Клубника', 'description' => 'Свежая', 'proteins' => 0.8, 'fats' => 0.4, 'carbs' => 7.5, 'calories' => 41],
                ['name' => 'Малина', 'description' => 'Свежая', 'proteins' => 0.8, 'fats' => 0.5, 'carbs' => 8.3, 'calories' => 46],
                ['name' => 'Черника', 'description' => 'Свежая', 'proteins' => 1.1, 'fats' => 0.6, 'carbs' => 8.2, 'calories' => 44],
                ['name' => 'Арбуз', 'description' => 'Свежий', 'proteins' => 0.6, 'fats' => 0.1, 'carbs' => 5.8, 'calories' => 27],
                ['name' => 'Дыня', 'description' => 'Свежая', 'proteins' => 0.6, 'fats' => 0.3, 'carbs' => 7.4, 'calories' => 35],
                ['name' => 'Гранат', 'description' => 'Свежий', 'proteins' => 0.9, 'fats' => 0.0, 'carbs' => 14.5, 'calories' => 72],
                ['name' => 'Хлеб пшеничный', 'description' => 'Белый', 'proteins' => 8.1, 'fats' => 1.0, 'carbs' => 48.8, 'calories' => 242],
                ['name' => 'Хлеб ржаной', 'description' => 'Черный', 'proteins' => 6.6, 'fats' => 1.2, 'carbs' => 34.2, 'calories' => 174],
                ['name' => 'Батон нарезной', 'description' => 'Пшеничный', 'proteins' => 7.5, 'fats' => 2.9, 'carbs' => 50.9, 'calories' => 262],
                ['name' => 'Лаваш тонкий', 'description' => 'Пшеничный', 'proteins' => 9.1, 'fats' => 1.2, 'carbs' => 55.7, 'calories' => 275],
                ['name' => 'Бублик', 'description' => 'Сдобное изделие', 'proteins' => 9.1, 'fats' => 1.1, 'carbs' => 57.1, 'calories' => 276],
                ['name' => 'Сушка', 'description' => 'Хлебобулочное изделие', 'proteins' => 11.0, 'fats' => 1.3, 'carbs' => 73.0, 'calories' => 330],
                ['name' => 'Круассан', 'description' => 'Слоеная выпечка', 'proteins' => 8.2, 'fats' => 21.0, 'carbs' => 43.9, 'calories' => 406],
                ['name' => 'Булочка сдобная', 'description' => 'Без начинки', 'proteins' => 7.6, 'fats' => 8.8, 'carbs' => 56.4, 'calories' => 339],
                ['name' => 'Пирожок печеный с капустой', 'description' => 'Выпечка', 'proteins' => 6.8, 'fats' => 8.0, 'carbs' => 30.7, 'calories' => 221],
                ['name' => 'Пирожок печеный с яблоком', 'description' => 'Выпечка', 'proteins' => 5.0, 'fats' => 5.5, 'carbs' => 39.6, 'calories' => 229],
                ['name' => 'Пицца Маргарита', 'description' => 'Готовая', 'proteins' => 9.3, 'fats' => 10.0, 'carbs' => 28.0, 'calories' => 239],
                ['name' => 'Блины', 'description' => 'Классические', 'proteins' => 6.1, 'fats' => 8.4, 'carbs' => 26.0, 'calories' => 233],
                ['name' => 'Оладьи', 'description' => 'На кефире', 'proteins' => 6.0, 'fats' => 7.0, 'carbs' => 31.0, 'calories' => 234],
                ['name' => 'Сырники', 'description' => 'Классические', 'proteins' => 18.6, 'fats' => 3.6, 'carbs' => 18.2, 'calories' => 183],
                ['name' => 'Печенье сахарное', 'description' => 'Классическое', 'proteins' => 7.0, 'fats' => 11.8, 'carbs' => 74.0, 'calories' => 417],
                ['name' => 'Галеты', 'description' => 'Сухое печенье', 'proteins' => 11.2, 'fats' => 1.4, 'carbs' => 72.4, 'calories' => 342],
                ['name' => 'Крекер', 'description' => 'Соленый', 'proteins' => 9.0, 'fats' => 14.0, 'carbs' => 67.0, 'calories' => 428],
                ['name' => 'Сок яблочный', 'description' => 'Без сахара, восстановленный', 'proteins' => 0.5, 'fats' => 0.1, 'carbs' => 10.1, 'calories' => 46],
                ['name' => 'Сок апельсиновый', 'description' => 'Без сахара', 'proteins' => 0.7, 'fats' => 0.2, 'carbs' => 10.4, 'calories' => 48],
                ['name' => 'Сок томатный', 'description' => 'Соль усредненно', 'proteins' => 1.0, 'fats' => 0.2, 'carbs' => 3.8, 'calories' => 21],
                ['name' => 'Сок виноградный', 'description' => 'Без сахара', 'proteins' => 0.3, 'fats' => 0.0, 'carbs' => 14.0, 'calories' => 60],
                ['name' => 'Сок мультифрукт', 'description' => 'Нектар/сок, усредненно', 'proteins' => 0.3, 'fats' => 0.1, 'carbs' => 11.5, 'calories' => 49],
                ['name' => 'Нектар персиковый', 'description' => 'Усредненно', 'proteins' => 0.3, 'fats' => 0.0, 'carbs' => 12.0, 'calories' => 52],
                ['name' => 'Морс клюквенный', 'description' => 'С сахаром, усредненно', 'proteins' => 0.1, 'fats' => 0.0, 'carbs' => 10.7, 'calories' => 44],
                ['name' => 'Компот из сухофруктов', 'description' => 'С сахаром, усредненно', 'proteins' => 0.3, 'fats' => 0.0, 'carbs' => 11.5, 'calories' => 48],
                ['name' => 'Квас хлебный', 'description' => 'Традиционный', 'proteins' => 0.2, 'fats' => 0.0, 'carbs' => 5.2, 'calories' => 27],
                ['name' => 'Чай с сахаром', 'description' => 'Черный чай, 2 ч.л. сахара на 200 мл', 'proteins' => 0.0, 'fats' => 0.0, 'carbs' => 5.0, 'calories' => 20],
                ['name' => 'Кофе с молоком и сахаром', 'description' => 'Растворимый/фильтр, усредненно', 'proteins' => 0.7, 'fats' => 0.8, 'carbs' => 5.5, 'calories' => 34],
                ['name' => 'Какао на молоке', 'description' => 'С сахаром, усредненно', 'proteins' => 3.2, 'fats' => 3.0, 'carbs' => 10.5, 'calories' => 82],
                ['name' => 'Йогурт питьевой сладкий', 'description' => '2-2.5% жирности', 'proteins' => 2.8, 'fats' => 2.2, 'carbs' => 11.0, 'calories' => 77],
                ['name' => 'Ряженка 3.2%', 'description' => 'Кисломолочный напиток', 'proteins' => 2.8, 'fats' => 3.2, 'carbs' => 4.1, 'calories' => 57],
                ['name' => 'Снежок', 'description' => 'Кисломолочный сладкий напиток', 'proteins' => 2.8, 'fats' => 2.5, 'carbs' => 10.8, 'calories' => 76],
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
                [...$product, 'uuid' => (string) Str::uuid()],
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
