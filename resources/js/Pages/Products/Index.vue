<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    products: {
        type: Array,
        required: true,
    },
});

const quantities = reactive(
    Object.fromEntries(props.products.map((product) => [product.id, 1])),
);

const form = useForm({
    product_id: null,
    quantity: 1,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
const errors = computed(() => page.props.errors || {});

const addToCart = (product) => {
    form.product_id = product.id;
    form.quantity = quantities[product.id] || 1;

    form.post(route('cart.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            quantities[product.id] = 1;
        },
    });
};
</script>

<template>
    <Head title="Products" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Products
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="flash.success" class="mb-4 rounded-md bg-green-50 p-4 text-green-700">
                    {{ flash.success }}
                </div>
                <div v-if="errors.quantity" class="mb-4 rounded-md bg-red-50 p-4 text-red-700">
                    {{ errors.quantity }}
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="product in products"
                        :key="product.id"
                        class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ product.name }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    Stock: {{ product.stock_quantity }}
                                </p>
                            </div>
                            <div class="text-lg font-semibold text-gray-900">
                                ${{ Number(product.price).toFixed(2) }}
                            </div>
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <input
                                v-model.number="quantities[product.id]"
                                type="number"
                                min="1"
                                :max="product.stock_quantity"
                                class="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <button
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-indigo-300"
                                :disabled="form.processing || product.stock_quantity === 0"
                                @click="addToCart(product)"
                            >
                                Add to cart
                            </button>
                        </div>

                        <p v-if="product.stock_quantity === 0" class="mt-2 text-sm text-red-500">
                            Out of stock
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
