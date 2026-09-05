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
        sku: 'KEY-CS2-PRIME',
        name: 'Counter-Strike 2 KEY',
        type: 'key',
        price: 1290,
        oldPrice: 1990,
        currency: 'RUB',
        image: 'assets/img.png',
    },
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
        sku: 'KEY-GTA5',
        name: 'GTA V STEAM KEY',
        type: 'key',
        price: 1990,
        oldPrice: 2490,
        currency: 'RUB',
        image: 'assets/img.png',
    },
    {
        sku: 'SUB-DISCORD-1M',
        name: 'Discord Nitro 1 месяц',
        type: 'subscription',
        price: 399,
        oldPrice: 599,
        currency: 'RUB',
        image: 'assets/img.png',
    },
    {
        sku: 'SUB-SPOTIFY-1M',
        name: 'Spotify Premium',
        type: 'subscription',
        price: 299,
        oldPrice: 499,
        currency: 'RUB',
        image: 'assets/img.png',
    },
]

export const popularProducts = products.slice(0, 5)

export const recommendedProducts = products.slice(5, 10)

export const otherProducts = products.slice(0, 5)