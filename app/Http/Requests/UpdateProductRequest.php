<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->getKey() : $product;

        return [
            'sku' => [
                'required', 'string', 'max:50',
                Rule::unique('products', 'sku')
                    ->whereNull('deleted_at')
                    ->ignore($productId),
            ],
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('products', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($productId),
            ],
            'unit' => ['required', 'string', 'max:20'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:9999999.99', 'gte:cost_price'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'A product with this SKU already exists.',
            'name.unique' => 'A product with this name already exists.',
            'selling_price.gte' => 'The selling price cannot be lower than the cost price.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}