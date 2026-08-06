<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ActivityLog;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    //اضافة منتج من قبل الادمن
    public function storeProduct(Request $request)
    {
        // 1. التحقق من الصلاحيات (أدمن فقط)
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول، هذه الصلاحية للأدمن فقط'], 403);
        }

        // 2. التحقق من صحة المدخلات
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'price'          => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description'    => 'nullable|string',
            'image'          => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // رفع صورة بحجم أقصى 2 ميجابايت
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $imagePath = null;

            // 3. معالجة وتخزين الصورة إن وجدت
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public'); 
                // سيتم حفظها في storage/app/public/products ويمكن الوصول لها عبر الرابط
            }

            // 4. إنشاء المنتج في قاعدة البيانات
            $product = Product::create([
                'name'           => $request->name,
                'price'          => $request->price,
                'stock_quantity' => $request->stock_quantity,
                'description'    => $request->description,
                'image'          => $imagePath ? asset('storage/' . $imagePath) : null, // رابط الصورة الكامل
            ]);

            return response()->json([
                'status' => 201,
                'message' => 'تم إضافة المنتج بنجاح',
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'حدث خطأ أثناء رفع المنتج',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //تعديل بيانات منتج الادمن
    public function updateProduct(Request $request, $id)
    {
        // 1. التحقق من الصلاحيات (أدمن فقط)
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول، هذه الصلاحية للأدمن فقط'], 403);
        }

        // 2. البحث عن المنتج
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'المنتج غير موجود'], 404);
        }

        // 3. التحقق من صحة المدخلات
        $validator = Validator::make($request->all(), [
            'name'           => 'sometimes|required|string|max:255',
            'price'          => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'description'    => 'nullable|string',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // 4. معالجة رفع الصورة الجديدة إن وجدت
            if ($request->hasFile('image')) {
                // إذا كان للمنتج صورة قديمة، نقوم بحذفها من التخزين لتوفير المساحة
                if ($product->image) {
                    // استخراج المسار النسبي من رابط الصورة الكامل
                    $oldPath = str_replace(asset('storage/'), '', $product->image);
                    Storage::disk('public')->delete($oldPath);
                }

                // تخزين الصورة الجديدة
                $imagePath = $request->file('image')->store('products', 'public');
                $product->image = asset('storage/' . $imagePath);
            }

            // 5. تحديث بيانات المنتج بالحقول المرسلة فقط
            $product->update($request->only([
                'name',
                'price',
                'stock_quantity',
                'description',
            ]));

            return response()->json([
                'status' => 200,
                'message' => 'تم تعديل بيانات المنتج بنجاح',
                'data' => $product
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'حدث خطأ أثناء تعديل المنتج',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // حذف منتج من قبل الأدمن
    public function deleteProduct($id)
    {
        // 1. التحقق من الصلاحيات (أدمن فقط)
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول، هذه الصلاحية للأدمن فقط'], 403);
        }

        // 2. البحث عن المنتج
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'المنتج غير موجود'], 404);
        }

        try {
            // 3. حذف الصورة المرتبطة من التخزين إن وجدت
            if ($product->image) {
                $oldPath = str_replace(asset('storage/'), '', $product->image);
                Storage::disk('public')->delete($oldPath);
            }

            // 4. حذف المنتج من قاعدة البيانات
            $product->delete();

            return response()->json([
                'status' => 200,
                'message' => 'تم حذف المنتج بنجاح'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'حدث خطأ أثناء حذف المنتج',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //عرض المنتجات مع فلترة الكمية متوفرة؟ وبالاسم.. للجميع
    public function indexProducts(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'reception', 'trainee', 'coach'])) {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $query = Product::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('price')) {
            // مثلاً: عرض المنتجات التي سعرها أقل من أو يساوي القيمة المدخلة
            $query->where('price', '<=', $request->price);
        }

        if ($request->boolean('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        $products = $query->latest()->get();

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب المنتجات بنجاح',
            'count' => $products->count(),
            'data' => $products
        ], 200);
    }

    // عرض تفاصيل منتج معين
    public function showProduct($id)
    {
    // التحقق من الصلاحيات (يمكنك تعديل الأدوار المسموح لها حسب الرغبة)
    if (!in_array(auth()->user()->role, ['admin', 'reception', 'trainee'])) {
        return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
    }

    // البحث عن المنتج
    $product = Product::find($id);
    if (!$product) {
        return response()->json(['message' => 'المنتج غير موجود'], 404);
    }

    return response()->json([
        'status' => 200,
        'message' => 'تم جلب تفاصيل المنتج بنجاح',
        'data' => $product
    ], 200);
    }

    //بيع منتج وخصم من المخزون
    public function sellProducts(Request $request)
    {
        //  أدمن أو استقبال فقط
        if (!in_array(auth()->user()->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id'         => 'nullable|exists:users,id',
            'customer_name'   => 'required_without:user_id|string|max:255',
            'customer_phone'  => 'nullable|string|max:20',
            'payment_method'  => 'required|in:cash,card,transfer',
            'notes'           => 'nullable|string',
            'items'           => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $total = 0;
                $saleItems = [];

                // 1. التحقق من المخزون + حساب الإجمالي
                foreach ($request->items as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);

                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("الكمية غير كافية للمنتج: {$product->name}");
                    }

                    $subtotal = $product->price * $item['quantity'];
                    $total += $subtotal;

                    $saleItems[] = [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->price,
                        'subtotal' => $subtotal,
                    ];
                }

                // 2. إنشاء الفاتورة
                $sale = Sale::create([
                    'user_id'        => $request->user_id,
                    'sold_by'        => auth()->id(),
                    'customer_name'  => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'total_amount'   => $total,
                    'payment_method' => $request->payment_method,
                    'notes'          => $request->notes,
                ]);

                // 3. إضافة التفاصيل + خصم المخزون
                foreach ($saleItems as $item) {
                    SaleItem::create([
                        'sale_id'    => $sale->id,
                        'product_id' => $item['product']->id,
                        'quantity'   => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal'   => $item['subtotal'],
                    ]);

                    $item['product']->decrement('stock_quantity', $item['quantity']);
                }

                $sale->load(['items.product', 'user', 'seller']);

                //  تسجيل نشاط الموظف 
                $addedByName = auth()->user()->full_name;

                $customerName = $sale->user_id
                    ? $sale->user->full_name . ' | عضوية: ' . $sale->user->membership_number
                    : ($sale->customer_name ?? 'زائر');

                $productsSummary = $sale->items->map(function ($item) {
                    return $item->product->name . ' × ' . $item->quantity;
                })->implode('، ');

                ActivityLog::log(
                    auth()->id(),
                    'sale',
                    'بيع منتجات',
                    [
                        'subject_type' => Sale::class,
                        'subject_id'   => $sale->id,
                        'details'      => 'المشتري: ' . $customerName . ' | الإجمالي: ' . $sale->total_amount . ' $',
                        'icon'         => 'sale',
                        'properties'   => [
                            'message' => 'تم بيع منتجات بقيمة: ' . $sale->total_amount . ' $' .
                                        ' | المشتري: ' . $customerName .
                                        ' | المنتجات: ' . $productsSummary .
                                        ' | طريقة الدفع: ' . $sale->payment_method .
                                        ' | بواسطة: ' . $addedByName
                        ]
                    ]
                );
                return response()->json([
                    'status'  => 201,
                    'message' => 'تم البيع بنجاح',
                    'data' => [
                        'sale_id'         => $sale->id,
                        'invoice_number'  => 'INV-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                        'date'            => $sale->created_at->format('Y-m-d H:i'),
                        'payment_method'  => $sale->payment_method,
                        'total_amount'    => (float) $sale->total_amount,
                        'notes'           => $sale->notes,

                        // بيانات المشتري
                        'customer' => $sale->user_id ? [
                            'type'              => 'member',
                            'id'                => $sale->user->id,
                            'name'              => $sale->user->full_name,
                            'membership_number' => $sale->user->membership_number,
                            'phone'             => $sale->user->phone,
                        ] : [
                            'type'  => 'guest',
                            'name'  => $sale->customer_name,
                            'phone' => $sale->customer_phone,
                        ],

                        // بيانات البائع
                        'seller' => [
                            'id'   => $sale->seller->id,
                            'name' => $sale->seller->full_name,
                            'role' => $sale->seller->role,
                        ],

                        // المنتجات المباعة
                        'items' => $sale->items->map(function ($item) {
                            return [
                                'product_id'   => $item->product_id,
                                'name'         => $item->product->name,
                                'quantity'     => $item->quantity,
                                'unit_price'   => (float) $item->unit_price,
                                'subtotal'     => (float) $item->subtotal,
                                'remaining_stock' => $item->product->stock_quantity,
                                'status'       => $item->product->status_label,
                            ];
                        }),
                    ]
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 400,
                'message' => $e->getMessage()
            ], 400);
        }    
    }

    //  عرض كل الفواتير 
    public function indexSales(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $query = Sale::with(['user:id,full_name,membership_number', 'seller:id,full_name'])
                    ->latest();

        // فلترة اختيارية
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $sales = $query->paginate(20);

        $data = $sales->getCollection()->map(function ($sale) {
            return [
                'sale_id'         => $sale->id,
                'invoice_number'  => 'INV-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                'date'            => $sale->created_at->format('Y-m-d H:i'),
                'total_amount'    => (float) $sale->total_amount,
                'payment_method'  => $sale->payment_method,
                'customer' => $sale->user_id ? [
                    'type' => 'member',
                    'name' => $sale->user->full_name,
                    'membership_number' => $sale->user->membership_number,
                ] : [
                    'type' => 'guest',
                    'name' => $sale->customer_name,
                ],
                'seller' => $sale->seller->full_name,
            ];
        });

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب الفواتير بنجاح',
            'count'   => $sales->total(),
            'data'    => $data,
            'pagination' => [
                'current_page' => $sales->currentPage(),
                'last_page'    => $sales->lastPage(),
                'per_page'     => $sales->perPage(),
            ]
        ]);
    }


    //تفاصيل فاتورة معينة 
    public function showSale($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $sale = Sale::with(['items.product', 'user', 'seller'])->find($id);

        if (!$sale) {
            return response()->json(['message' => 'الفاتورة غير موجودة'], 404);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب تفاصيل الفاتورة بنجاح',
            'data' => [
                'sale_id'         => $sale->id,
                'invoice_number'  => 'INV-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                'date'            => $sale->created_at->format('Y-m-d H:i'),
                'payment_method'  => $sale->payment_method,
                'total_amount'    => (float) $sale->total_amount,
                'notes'           => $sale->notes,
                'status'          => $sale->status,

                'customer' => $sale->user_id ? [
                    'type'              => 'member',
                    'id'                => $sale->user->id,
                    'name'              => $sale->user->full_name,
                    'membership_number' => $sale->user->membership_number,
                    'phone'             => $sale->user->phone,
                ] : [
                    'type'  => 'guest',
                    'name'  => $sale->customer_name,
                    'phone' => $sale->customer_phone,
                ],

                'seller' => [
                    'id'   => $sale->seller->id,
                    'name' => $sale->seller->full_name,
                    'role' => $sale->seller->role,
                ],

                'items' => $sale->items->map(function ($item) {
                    return [
                        'product_id'      => $item->product_id,
                        'name'            => $item->product->name,
                        'quantity'        => $item->quantity,
                        'unit_price'      => (float) $item->unit_price,
                        'subtotal'        => (float) $item->subtotal,
                        'remaining_stock' => $item->product->stock_quantity,
                        'status'          => $item->product->status_label ?? null,
                    ];
                }),
            ]
        ]);
        
    }


}
