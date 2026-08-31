<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo que representa un producto del catálogo del e-commerce.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $price
 * @property int $stock
 * @property string|null $image_url
 * @property bool $active
 */
class Product extends Model
{
    /**
     * Atributos asignables de forma masiva.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'image_url',
        'active',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Detalle de órdenes en los que aparece este producto.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scope para filtrar únicamente productos activos (visibles públicamente).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope para buscar productos por nombre o descripción.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Verifica si hay stock suficiente para una cantidad solicitada.
     */
    public function hasStockFor(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }
}
