<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { ShoppingCart } from 'lucide-vue-next';

interface StoreProduct {
    id: number;
    name: string;
    description: string;
    price: number | string;
    image?: string | null;
    quantity: number;
}

interface CartItem {
    productId: number;
    name: string;
    price: number;
    image?: string | null;
    quantity: number;
    stock: number;
}

type PaymentStatus = 'idle' | 'processing' | 'success' | 'failed' | 'pending';

const props = defineProps<{
    products?: StoreProduct[];
}>();

const selectedPaymentMethod = ref('Stripe');
const paymentStatus = ref<PaymentStatus>('idle');
const paymentMessage = ref('');
const showCartPopup = ref(false);

const productQuantitySelection = reactive<Record<number, number>>({});
const cart = ref<CartItem[]>([]);

const checkoutForm = reactive({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
});

const products = computed<StoreProduct[]>(() => {
    if (!props.products) {
        return [];
    }

    return props.products.map((product) => ({
        ...product,
        price: Number(product.price) || 0,
        quantity: Number(product.quantity) || 0,
    }));
});

const cartItemCount = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.quantity, 0);
});

const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0);
});

const vat = computed(() => subtotal.value * 0.22);
const total = computed(() => subtotal.value + vat.value);

const formatPrice = (price: number | string): string => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(price) || 0);
};

const selectedQtyFor = (product: StoreProduct): number => {
    return productQuantitySelection[product.id] ?? 1;
};

const increaseSelectedQty = (product: StoreProduct): void => {
    const current = selectedQtyFor(product);
    if (current < product.quantity) {
        productQuantitySelection[product.id] = current + 1;
    }
};

const decreaseSelectedQty = (product: StoreProduct): void => {
    const current = selectedQtyFor(product);
    if (current > 1) {
        productQuantitySelection[product.id] = current - 1;
    }
};

const addToCart = (product: StoreProduct): void => {
    if (product.quantity <= 0) {
        return;
    }

    const requestedQty = Math.max(1, Math.min(selectedQtyFor(product), product.quantity));
    const existing = cart.value.find((item) => item.productId === product.id);

    if (existing) {
        existing.quantity = Math.min(existing.quantity + requestedQty, existing.stock);
    } else {
        cart.value.push({
            productId: product.id,
            name: product.name,
            price: Number(product.price) || 0,
            image: product.image,
            quantity: requestedQty,
            stock: product.quantity,
        });
    }
};

const increaseCartQty = (productId: number): void => {
    const item = cart.value.find((entry) => entry.productId === productId);
    if (item && item.quantity < item.stock) {
        item.quantity += 1;
    }
};

const decreaseCartQty = (productId: number): void => {
    const item = cart.value.find((entry) => entry.productId === productId);
    if (!item) {
        return;
    }

    if (item.quantity > 1) {
        item.quantity -= 1;
    } else {
        cart.value = cart.value.filter((entry) => entry.productId !== productId);
    }
};

const removeFromCart = (productId: number): void => {
    cart.value = cart.value.filter((item) => item.productId !== productId);
};

const resetCheckoutState = (): void => {
    checkoutForm.firstName = '';
    checkoutForm.lastName = '';
    checkoutForm.email = '';
    checkoutForm.phone = '';
    selectedPaymentMethod.value = 'Stripe';
};

const checkoutDisabled = computed(() => {
    return cart.value.length === 0 || paymentStatus.value === 'processing';
});

const payNow = async (): Promise<void> => {
    if (checkoutDisabled.value) {
        return;
    }

    paymentStatus.value = 'processing';
    paymentMessage.value = 'Processing payment securely...';

    await new Promise((resolve) => setTimeout(resolve, 900));

    if (!checkoutForm.firstName || !checkoutForm.lastName || !checkoutForm.email || !checkoutForm.phone) {
        paymentStatus.value = 'failed';
        paymentMessage.value = 'Payment failed: please fill in all customer details.';
        return;
    }

    const randomState = Math.random();

    if (randomState < 0.6) {
        paymentStatus.value = 'success';
        paymentMessage.value = 'Payment successful. Order saved and cart cleared.';
        // Placeholder for backend order save integration
        // await axios.post('/api/orders', { ...checkoutForm, items: cart.value, total: total.value });
        cart.value = [];
        resetCheckoutState();
        return;
    }

    if (randomState < 0.8) {
        paymentStatus.value = 'pending';
        paymentMessage.value = 'Payment pending. Please wait for provider confirmation.';
        return;
    }

    paymentStatus.value = 'failed';
    paymentMessage.value = 'Payment failed. Your cart is preserved, please try again.';
};
</script>

<template>
    <div class="h-full w-full rounded-3xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-950">
        <div class="mb-6 flex items-end justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Store</p>
                <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white">Products</h2>
            </div>
            <button
                type="button"
                class="relative rounded-xl cursor-pointer bg-zinc-900 px-2 py-2 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                @click="showCartPopup = true"
            >
                <ShoppingCart class="" />
                <span
                    class="absolute -right-2 -top-2 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-zinc-200 px-1 text-xs font-bold text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100"
                >
                    {{ cartItemCount }}
                </span>
            </button>
        </div>

        <div>
            <section>
                <div
                    v-if="products.length === 0"
                    class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300"
                >
                    No products database yet.
                </div>
                <div v-else class="grid grid-cols-3 gap-4">
                    <article
                        v-for="product in products"
                        :key="product.id"
                        class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <img
                            :src="product.image || 'https://via.placeholder.com/600x380?text=No+Image'"
                            :alt="product.name"
                            class="mb-4 h-40 w-full rounded-lg object-cover"
                        />
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <h4 class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ product.name }}
                            </h4>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="product.quantity > 0
                                    ? 'bg-zinc-900 text-white dark:bg-zinc-200 dark:text-zinc-900'
                                    : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'"
                            >
                                {{ product.quantity > 0 ? `${product.quantity} available` : 'Out of stock' }}
                            </span>
                        </div>
                        <p class="mb-3 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ product.description }}
                        </p>

                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-xl font-bold text-zinc-900 dark:text-white">
                                {{ formatPrice(product.price) }}
                            </p>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg border border-zinc-300 px-2 py-1 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-200"
                                    :disabled="selectedQtyFor(product) <= 1"
                                    @click="decreaseSelectedQty(product)"
                                >
                                    -
                                </button>
                                <span class="w-6 text-center text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ selectedQtyFor(product) }}</span>
                                <button
                                    type="button"
                                    class="rounded-lg border border-zinc-300 px-2 py-1 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-200"
                                    :disabled="selectedQtyFor(product) >= product.quantity"
                                    @click="increaseSelectedQty(product)"
                                >
                                    +
                                </button>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="w-full rounded-xl cursor-pointer bg-zinc-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                            :disabled="product.quantity <= 0"
                            @click="addToCart(product)"
                        >
                            Add to cart
                        </button>
                    </article>
                </div>
            </section>
        </div>

        <div
            v-if="showCartPopup"
            class="fixed inset-0 z-50 flex items-start justify-end bg-black/40 p-4"
            @click.self="showCartPopup = false"
        >
            <section class="max-h-[95vh] w-full max-w-md overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-4 shadow-2xl dark:border-zinc-700 dark:bg-zinc-950">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Cart & Checkout</h3>
                    <button
                        type="button"
                        class="rounded-lg cursor-pointer border border-zinc-300 px-2 py-1 text-xs text-zinc-700 dark:border-zinc-700 dark:text-zinc-200"
                        @click="showCartPopup = false"
                    >
                        Close
                    </button>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <h4 class="mb-3 text-base font-semibold text-zinc-900 dark:text-white">Cart</h4>
                    <div v-if="cart.length === 0" class="text-sm text-zinc-500 dark:text-zinc-400">
                        Your cart is empty.
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="item in cart"
                            :key="item.productId"
                            class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950"
                        >
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ item.name }}</p>
                                <button
                                    type="button"
                                    class="text-xs cursor-pointer font-medium text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100"
                                    @click="removeFromCart(item.productId)"
                                >
                                    Remove
                                </button>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded cursor-pointer border border-zinc-300 px-2 py-0.5 text-xs dark:border-zinc-700"
                                        @click="decreaseCartQty(item.productId)"
                                    >
                                        -
                                    </button>
                                    <span class="min-w-6 text-center text-sm">{{ item.quantity }}</span>
                                    <button
                                        type="button"
                                        class="rounded cursor-pointer border border-zinc-300 px-2 py-0.5 text-xs dark:border-zinc-700"
                                        :disabled="item.quantity >= item.stock"
                                        @click="increaseCartQty(item.productId)"
                                    >
                                        +
                                    </button>
                                </div>
                                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                    {{ formatPrice(item.price * item.quantity) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <h4 class="mb-3 text-base font-semibold text-zinc-900 dark:text-white">Checkout</h4>
                    <div class="grid gap-2">
                        <input v-model="checkoutForm.firstName" type="text" placeholder="First name" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                        <input v-model="checkoutForm.lastName" type="text" placeholder="Last name" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                        <input v-model="checkoutForm.email" type="email" placeholder="Email" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                        <input v-model="checkoutForm.phone" type="text" placeholder="Phone" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    </div>

                    <div class="mt-3 rounded-xl border border-zinc-200 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Payment method</span>
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ selectedPaymentMethod }}</span>
                        </div>
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                            <span>{{ formatPrice(subtotal) }}</span>
                        </div>
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">VAT (22%)</span>
                            <span>{{ formatPrice(vat) }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-zinc-200 pt-2 font-semibold dark:border-zinc-700">
                            <span>Total</span>
                            <span>{{ formatPrice(total) }}</span>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="mt-4 w-full cursor-pointer rounded-xl bg-zinc-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                        :disabled="checkoutDisabled"
                        @click="payNow"
                    >
                        {{ paymentStatus === 'processing' ? 'Processing...' : 'Checkout' }}
                    </button>

                    <p
                        v-if="paymentStatus !== 'idle'"
                        class="mt-3 rounded-lg border px-3 py-2 text-sm"
                        :class="{
                            'border-zinc-300 bg-zinc-100 text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100': paymentStatus === 'processing' || paymentStatus === 'pending',
                            'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300': paymentStatus === 'success',
                            'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300': paymentStatus === 'failed',
                        }"
                    >
                        {{ paymentMessage }}
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>