export type ProductType =
    | 'topup'
    | 'key'
    | 'subscription'
    | 'giftcard'

export interface Product {
    sku: string
    name: string
    type: ProductType
    price: number
    oldPrice?: number
    currency: 'RUB'
    image: string
}

export const products: Product[] = [
    {
        sku: 'KEY-DOOM-2016',
        name: '🔖 DOOM 2016 ⚙ STEAM KEY 🔑',
        type: 'key',
        price: 990,
        oldPrice: 1990,
        currency: 'RUB',
        image: 'assets/img.png',
    },
    {
        sku: 'KEY-DOOM-2016-2',
        name: '🔖 DOOM 2016 ⚙ STEAM KEY 🔑',
        type: 'key',
        price: 990,
        oldPrice: 1990,
        currency: 'RUB',
        image: 'assets/img.png',
    },
    {
        sku: 'KEY-DOOM-2016-3',
        name: '🔖 DOOM 2016 ⚙ STEAM KEY 🔑',
        type: 'key',
        price: 990,
        oldPrice: 1990,
        currency: 'RUB',
        image: 'assets/img.png',
    },
    {
        sku: 'KEY-DOOM-2016-4',
        name: '🔖 DOOM 2016 ⚙ STEAM KEY 🔑',
        type: 'key',
        price: 990,
        oldPrice: 1990,
        currency: 'RUB',
        image: 'assets/img.png',
    },
    {
        sku: 'KEY-DOOM-2016-5',
        name: '🔖 DOOM 2016 ⚙ STEAM KEY 🔑',
        type: 'key',
        price: 990,
        oldPrice: 1990,
        currency: 'RUB',
        image: 'assets/img.png',
    },

    {
        sku: 'KEY-GTA5',
        name: 'GTA V STEAM KEY',
        type: 'key',
        price: 1290,
        oldPrice: 1990,
        currency: 'RUB',
        image: 'assets/img.png',
    },
    {
        sku: 'KEY-CS2',
        name: 'Counter-Strike 2 KEY',
        type: 'key',
        price: 1490,
        oldPrice: 1990,
        currency: 'RUB',
        image: 'assets/img.png',
    },
    {
        sku: 'SUB-DISCORD',
        name: 'Discord Nitro 1 месяц',
        type: 'subscription',
        price: 399,
        oldPrice: 599,
        currency: 'RUB',
        image: 'assets/img.png',
    },
    {
        sku: 'SUB-SPOTIFY',
        name: 'Spotify Premium',
        type: 'subscription',
        price: 299,
        oldPrice: 499,
        currency: 'RUB',
        image: 'assets/img.png',
    },
    {
        sku: 'SUB-YOUTUBE',
        name: 'YouTube Premium',
        type: 'subscription',
        price: 499,
        oldPrice: 699,
        currency: 'RUB',
        image: 'assets/img.png',
    },
]

export const popularProducts = products.slice(0, 5)

export const recommendedProducts = products.slice(5, 10)

export const otherProducts = products.slice(0, 5)