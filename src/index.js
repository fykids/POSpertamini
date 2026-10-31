import { Hono } from "hono";

const app = new Hono();
const fail = (c, status, message) =>
    c.json({ success: false, message }, status);
const asNumber = (value) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
};
const positiveInteger = (value) => {
    const parsed = asNumber(value);
    return parsed !== null && Number.isInteger(parsed) && parsed > 0
        ? parsed
        : null;
};
const positiveNumber = (value) => {
    const parsed = asNumber(value);
    return parsed !== null && parsed > 0 ? parsed : null;
};
const money = (value) => Math.round((value + Number.EPSILON) * 100) / 100;
const liters = (value) => Math.round((value + Number.EPSILON) * 1000) / 1000;

app.use("/api/*", async (c, next) => {
    const bridgeSecret = c.env.BRIDGE_SECRET_KEY;
    const authorization = c.req.header("Authorization");

    if (
        typeof bridgeSecret !== "string" ||
        bridgeSecret.length === 0 ||
        authorization !== `Bearer ${bridgeSecret}`
    ) {
        return fail(c, 401, "Unauthorized bridge access.");
    }

    await next();
});

app.post("/api/auth/login", async (c) => {
    const body = await c.req.json().catch(() => null);
    const username =
        typeof body?.username === "string" ? body.username.trim() : "";
    if (!username || username.length > 255)
        return fail(c, 422, "Username tidak valid.");

    const user = await c.env.DB.prepare(
        "SELECT * FROM users WHERE name = ? LIMIT 1",
    )
        .bind(username)
        .first();
    return c.json({ success: true, user: user ?? null });
});

const numberOrZero = (value) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : 0;
};

const productPayload = (body) => ({
    name: typeof body?.name === "string" ? body.name.trim() : String(body?.name ?? "").trim(),
    price: numberOrZero(body?.price_per_liter ?? body?.pricePerLiter),
    cost: numberOrZero(body?.buying_price_per_liter ?? body?.buyingPricePerLiter ?? body?.cost_per_liter ?? body?.costPerLiter),
    stock: numberOrZero(body?.current_stock ?? body?.currentStock),
    capacity: numberOrZero(body?.capacity),
    note: typeof (body?.note ?? body?.supplier_note ?? body?.supplierNote) === "string"
        ? String(body.note ?? body.supplier_note ?? body.supplierNote).trim().slice(0, 2000)
        : null,
});

app.get("/api/products", async (c) => {
    const result = await c.env.DB.prepare(
        "SELECT id, name, price_per_liter, buying_price_per_liter, current_stock, capacity, created_at, updated_at FROM products ORDER BY name, id",
    ).all();
    return c.json({ success: true, data: result.results });
});

app.get("/api/products/:id", async (c) => {
    const product = await c.env.DB.prepare(
        "SELECT * FROM products WHERE id = ?",
    )
        .bind(positiveInteger(c.req.param("id")))
        .first();
    return product
        ? c.json({ success: true, data: product })
        : fail(c, 404, "Produk tidak ditemukan.");
});
app.post("/api/products", async (c) => {
    try {
        const data = productPayload(await c.req.json().catch(() => null));
        if (!data.name || data.price <= 0 || data.cost < 0 || data.stock < 0 || data.capacity <= 0 || data.stock > data.capacity) {
            console.warn("Product validation failed", { name: data.name, price: data.price, cost: data.cost, stock: data.stock, capacity: data.capacity });
            return fail(c, 422, "Data produk tidak valid. Pastikan nama, harga jual, dan kapasitas benar.");
        }
        const result = await c.env.DB.prepare(
            "INSERT INTO products (name, price_per_liter, buying_price_per_liter, current_stock, capacity, created_at, updated_at) VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
        ).bind(data.name, money(data.price), money(data.cost), liters(data.stock), liters(data.capacity)).run();
        const productId = result.meta.last_row_id;
        if (data.stock > 0) {
            await c.env.DB.batch([
                c.env.DB.prepare("INSERT INTO stock_refills (product_id, liter_added, buying_price_per_liter, total_cost, supplier_note, created_at, updated_at) VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))")
                    .bind(productId, liters(data.stock), money(data.cost), money(data.stock * data.cost), data.note),
            ]);
        }
        return c.json({ success: true, data: await c.env.DB.prepare("SELECT * FROM products WHERE id = ?").bind(productId).first() }, 201);
    } catch (error) {
        console.error("Product insert failed", { message: error instanceof Error ? error.message : String(error) });
        return fail(c, 500, "Gagal menyimpan produk ke database.");
    }
});
app.put("/api/products/:id", async (c) => {
    const id = positiveInteger(c.req.param("id"));
    const data = productPayload(await c.req.json().catch(() => null));
    if (
        !id ||
        !data.name ||
        !data.price ||
        data.cost < 0 ||
        data.stock === null ||
        data.stock < 0 ||
        !data.capacity ||
        data.stock > data.capacity
    )
        return fail(c, 422, "Data produk tidak valid.");
    const result = await c.env.DB.prepare(
        "UPDATE products SET name=?, price_per_liter=?, buying_price_per_liter=?, current_stock=?, capacity=?, updated_at=datetime('now') WHERE id=?",
    )
        .bind(
            data.name,
            money(data.price),
            money(data.cost),
            liters(data.stock),
            liters(data.capacity),
            id,
        )
        .run();
    if (!result.meta.changes) return fail(c, 404, "Produk tidak ditemukan.");
    return c.json({
        success: true,
        data: await c.env.DB.prepare("SELECT * FROM products WHERE id=?")
            .bind(id)
            .first(),
    });
});
app.delete("/api/products/:id", async (c) => {
    const result = await c.env.DB.prepare("DELETE FROM products WHERE id=?")
        .bind(positiveInteger(c.req.param("id")))
        .run();
    return result.meta.changes
        ? c.json({ success: true, data: null })
        : fail(c, 404, "Produk tidak ditemukan.");
});

app.get("/api/dashboard/stats", async (c) => {
    const [sales, tx, cost] = await c.env.DB.batch([
        c.env.DB.prepare(
            "SELECT COALESCE(SUM(total_amount),0) amount, COALESCE(SUM(liter_qty),0) liters FROM transactions WHERE date(created_at)=date('now','localtime')",
        ),
        c.env.DB.prepare(
            "SELECT COUNT(*) count FROM transactions WHERE date(created_at)=date('now','localtime')",
        ),
        c.env.DB.prepare(
            "SELECT COALESCE(SUM(t.liter_qty * p.buying_price_per_liter),0) amount FROM transactions t JOIN products p ON p.id=t.product_id WHERE date(t.created_at)=date('now','localtime')",
        ),
    ]);
    return c.json({
        success: true,
        data: {
            sales: sales.results[0],
            transactions: tx.results[0].count,
            cost: cost.results[0].amount,
            profit:
                Number(sales.results[0].amount) -
                Number(cost.results[0].amount),
        },
    });
});

app.get("/api/transactions", async (c) => {
    const page = Math.max(1, Number(c.req.query("page") || 1));
    const perPage = Math.min(
        100,
        Math.max(10, Number(c.req.query("per_page") || 15)),
    );
    const search = `%${c.req.query("search") || ""}%`;
    const date = c.req.query("date") || "";
    const where =
        "WHERE (t.invoice_number LIKE ? OR p.name LIKE ?) AND (? = '' OR date(t.created_at)=?)";
    const [rows, count] = await c.env.DB.batch([
        c.env.DB.prepare(
            `SELECT t.*,p.name product_name FROM transactions t JOIN products p ON p.id=t.product_id ${where} ORDER BY t.id DESC LIMIT ? OFFSET ?`,
        ).bind(search, search, date, date, perPage, (page - 1) * perPage),
        c.env.DB.prepare(
            `SELECT COUNT(*) count FROM transactions t JOIN products p ON p.id=t.product_id ${where}`,
        ).bind(search, search, date, date),
    ]);
    return c.json({
        success: true,
        data: {
            data: rows.results,
            meta: { page, per_page: perPage, total: count.results[0].count },
        },
    });
});

app.get("/api/expenses", async (c) => {
    const result = await c.env.DB.prepare(
        "SELECT e.*,u.name user_name FROM expenses e LEFT JOIN users u ON u.id=e.user_id ORDER BY e.date DESC,e.id DESC",
    ).all();
    return c.json({ success: true, data: result.results });
});
app.post("/api/expenses", async (c) => {
    const b = await c.req.json().catch(() => null);
    const amount = positiveNumber(b?.amount);
    const userId = positiveInteger(b?.user_id ?? b?.userId);
    const category = typeof b?.category === "string" ? b.category.trim() : "";
    const date = typeof b?.date === "string" ? b.date : "";
    if (!amount || !userId || !category || !date)
        return fail(c, 422, "Data pengeluaran tidak valid.");
    const result = await c.env.DB.prepare(
        "INSERT INTO expenses(user_id,category,amount,note,date,created_at) VALUES(?,?,?,?,?,datetime('now'))",
    )
        .bind(
            userId,
            category,
            money(amount),
            typeof b?.note === "string" ? b.note.trim() : null,
            date,
        )
        .run();
    return c.json(
        { success: true, data: { id: result.meta.last_row_id } },
        201,
    );
});
app.delete("/api/expenses/:id", async (c) => {
    const r = await c.env.DB.prepare("DELETE FROM expenses WHERE id=?")
        .bind(positiveInteger(c.req.param("id")))
        .run();
    return r.meta.changes
        ? c.json({ success: true, data: null })
        : fail(c, 404, "Pengeluaran tidak ditemukan.");
});

app.get("/api/reports/financial", async (c) => {
    const period = c.req.query("period") || "month";
    const modifier =
        { today: "0 day", week: "-6 days", month: "start of month" }[period] ||
        "start of month";
    const [income, expenses, capital, flows] = await c.env.DB.batch([
        c.env.DB.prepare(
            "SELECT COALESCE(SUM(total_amount),0) value FROM transactions WHERE date(created_at)>=date('now',?)",
        ).bind(modifier),
        c.env.DB.prepare(
            "SELECT COALESCE(SUM(amount),0) value FROM expenses WHERE date(date)>=date('now',?)",
        ).bind(modifier),
        c.env.DB.prepare(
            "SELECT COALESCE(SUM(total_cost),0) value FROM stock_refills WHERE date(created_at)>=date('now',?)",
        ).bind(modifier),
        c.env.DB.prepare(
            "SELECT 'Pemasukan penjualan' type,total_amount amount,created_at date,invoice_number note FROM transactions WHERE date(created_at)>=date('now',?) UNION ALL SELECT 'Pengeluaran operasional',-amount,date,note FROM expenses WHERE date(date)>=date('now',?) UNION ALL SELECT 'Modal stok',-total_cost,created_at,supplier_note FROM stock_refills WHERE date(created_at)>=date('now',?) ORDER BY date DESC",
        ).bind(modifier, modifier, modifier),
    ]);
    const i = Number(income.results[0].value),
        e = Number(expenses.results[0].value),
        cap = Number(capital.results[0].value);
    return c.json({
        success: true,
        data: {
            income: i,
            expenses: e,
            capital: cap,
            net_profit: i - e - cap,
            flows: flows.results,
        },
    });
});

app.get("/api/users", async (c) => {
    const r = await c.env.DB.prepare(
        "SELECT id,name,role,created_at,updated_at FROM users ORDER BY name",
    ).all();
    return c.json({ success: true, data: r.results });
});
app.post("/api/users", async (c) => {
    const b = await c.req.json().catch(() => null);
    const name = typeof b?.name === "string" ? b.name.trim() : "";
    const password = typeof b?.password === "string" ? b.password : "";
    const role = ["admin", "cashier"].includes(b?.role) ? b.role : "cashier";
    if (!name || !password.startsWith("$2"))
        return fail(c, 422, "Username dan password hash bcrypt wajib diisi.");
    try {
        const r = await c.env.DB.prepare(
            "INSERT INTO users(name,password,role,created_at,updated_at) VALUES(?,?,?,?,?)",
        )
            .bind(
                name,
                password,
                role,
                new Date().toISOString(),
                new Date().toISOString(),
            )
            .run();
        return c.json(
            { success: true, data: { id: r.meta.last_row_id, name, role } },
            201,
        );
    } catch {
        return fail(c, 409, "Username sudah digunakan.");
    }
});
app.put("/api/users/:id", async (c) => {
    const id = positiveInteger(c.req.param("id"));
    const b = await c.req.json().catch(() => null);
    const name = typeof b?.name === "string" ? b.name.trim() : "";
    const role = ["admin", "cashier"].includes(b?.role) ? b.role : "cashier";
    const password =
        typeof b?.password === "string" && b.password.startsWith("$2")
            ? b.password
            : null;
    if (!id || !name) return fail(c, 422, "Data user tidak valid.");
    const r = password
        ? await c.env.DB.prepare(
              "UPDATE users SET name=?,role=?,password=?,updated_at=datetime('now') WHERE id=?",
          )
              .bind(name, role, password, id)
              .run()
        : await c.env.DB.prepare(
              "UPDATE users SET name=?,role=?,updated_at=datetime('now') WHERE id=?",
          )
              .bind(name, role, id)
              .run();
    return r.meta.changes
        ? c.json({ success: true, data: { id, name, role } })
        : fail(c, 404, "User tidak ditemukan.");
});
app.delete("/api/users/:id", async (c) => {
    const r = await c.env.DB.prepare("DELETE FROM users WHERE id=?")
        .bind(positiveInteger(c.req.param("id")))
        .run();
    return r.meta.changes
        ? c.json({ success: true, data: null })
        : fail(c, 404, "User tidak ditemukan.");
});

app.get("/api/shifts/open", async (c) => {
    const userId = positiveInteger(c.req.query("userId"));
    if (userId === null)
        return fail(c, 422, "userId harus berupa bilangan bulat positif.");

    const shift = await c.env.DB.prepare(
        "SELECT id, user_id, total_price_per_liter, total_cash_expected, status, created_at, updated_at FROM shifts WHERE user_id = ? AND status = 'OPEN' ORDER BY id DESC LIMIT 1",
    )
        .bind(userId)
        .first();

    return c.json({ success: true, data: shift ?? null });
});

app.post("/api/shifts", async (c) => {
    const body = await c.req.json().catch(() => null);
    const userId = positiveInteger(body?.userId);
    const totalPricePerLiter = positiveNumber(body?.totalPricePerLiter);
    if (userId === null || totalPricePerLiter === null)
        return fail(c, 422, "Data shift tidak valid.");

    const active = await c.env.DB.prepare(
        "SELECT id FROM shifts WHERE user_id = ? AND status = 'OPEN' LIMIT 1",
    )
        .bind(userId)
        .first();
    if (active)
        return fail(c, 409, "Pengguna masih memiliki shift yang terbuka.");

    const created = await c.env.DB.prepare(
        "INSERT INTO shifts (user_id, total_price_per_liter, total_cash_expected, status, created_at, updated_at) VALUES (?, ?, 0, 'OPEN', datetime('now'), datetime('now'))",
    )
        .bind(userId, money(totalPricePerLiter))
        .run();
    const shift = await c.env.DB.prepare(
        "SELECT id, user_id, total_price_per_liter, total_cash_expected, status, created_at, updated_at FROM shifts WHERE id = ?",
    )
        .bind(created.meta.last_row_id)
        .first();

    return c.json({ success: true, data: shift }, 201);
});

app.post("/api/shifts/:shiftId/close", async (c) => {
    const body = await c.req.json().catch(() => null);
    const shiftId = positiveInteger(c.req.param("shiftId"));
    const userId = positiveInteger(body?.userId);
    if (shiftId === null || userId === null)
        return fail(c, 422, "Data shift tidak valid.");

    const result = await c.env.DB.prepare(
        "UPDATE shifts SET status = 'CLOSED', updated_at = datetime('now') WHERE id = ? AND user_id = ? AND status = 'OPEN'",
    )
        .bind(shiftId, userId)
        .run();
    if (result.meta.changes !== 1)
        return fail(
            c,
            409,
            "Shift tidak ditemukan, bukan milik pengguna, atau sudah ditutup.",
        );

    return c.json({ success: true, data: { id: shiftId, status: "CLOSED" } });
});

app.post("/api/pos/checkout", async (c) => {
    const body = await c.req.json().catch(() => null);
    const invoice =
        typeof body?.invoice === "string" ? body.invoice.trim() : "";
    const shiftId = positiveInteger(body?.shiftId);
    const productId = positiveInteger(body?.productId);
    const userId = positiveInteger(body?.userId);
    const type = typeof body?.type === "string" ? body.type.toUpperCase() : "";
    const value = positiveNumber(body?.value);
    if (
        !invoice ||
        invoice.length > 255 ||
        shiftId === null ||
        productId === null ||
        userId === null ||
        value === null ||
        !["RUPIAH", "LITER"].includes(type)
    ) {
        return fail(c, 422, "Data transaksi tidak valid atau tidak lengkap.");
    }

    const [shiftQuery, productQuery, invoiceQuery] = await c.env.DB.batch([
        c.env.DB.prepare(
            "SELECT id FROM shifts WHERE id = ? AND user_id = ? AND status = 'OPEN'",
        ).bind(shiftId, userId),
        c.env.DB.prepare(
            "SELECT id, name, price_per_liter, current_stock FROM products WHERE id = ?",
        ).bind(productId),
        c.env.DB.prepare(
            "SELECT id FROM transactions WHERE invoice_number = ?",
        ).bind(invoice),
    ]);
    if (!shiftQuery.results[0])
        return fail(
            c,
            409,
            "Shift tidak ditemukan, bukan milik pengguna, atau sudah ditutup.",
        );
    if (!productQuery.results[0])
        return fail(c, 404, "Produk BBM tidak ditemukan.");
    if (invoiceQuery.results[0])
        return fail(c, 409, "Nomor invoice sudah pernah diproses.");

    const product = productQuery.results[0];
    const pricePerLiter = money(Number(product.price_per_liter));
    if (pricePerLiter <= 0)
        return fail(c, 409, "Harga per liter produk tidak valid.");
    const literQty =
        type === "RUPIAH" ? liters(value / pricePerLiter) : liters(value);
    const totalAmount =
        type === "RUPIAH" ? money(value) : money(literQty * pricePerLiter);
    if (literQty <= 0 || Number(product.current_stock) < literQty)
        return fail(
            c,
            409,
            `Stok BBM tidak cukup. Sisa stok: ${product.current_stock} liter.`,
        );

    await c.env.DB.batch([
        c.env.DB.prepare(
            "INSERT INTO transactions (invoice_number, shift_id, product_id, input_type, price_per_liter, liter_qty, total_amount, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
        ).bind(
            invoice,
            shiftId,
            productId,
            type,
            pricePerLiter,
            literQty,
            totalAmount,
        ),
        c.env.DB.prepare(
            "UPDATE products SET current_stock = current_stock - ?, updated_at = datetime('now') WHERE id = ?",
        ).bind(literQty, productId),
        c.env.DB.prepare(
            "UPDATE shifts SET total_cash_expected = total_cash_expected + ?, updated_at = datetime('now') WHERE id = ? AND status = 'OPEN'",
        ).bind(totalAmount, shiftId),
    ]);

    return c.json(
        {
            success: true,
            data: {
                invoice,
                shiftId,
                productName: product.name,
                pricePerLiter,
                literQty,
                totalAmount,
            },
        },
        201,
    );
});

app.post("/api/inventory/refill", async (c) => {
    const body = await c.req.json().catch(() => null);
    const productId = positiveInteger(body?.productId ?? body?.product_id);
    const literAdded = positiveNumber(body?.literAdded ?? body?.liter_added);
    const buyingPricePerLiter = positiveNumber(body?.buyingPricePerLiter ?? body?.buying_price_per_liter);
    const supplierNote =
        typeof body?.supplierNote === "string"
            ? body.supplierNote.trim().slice(0, 2000)
            : null;
    if (
        productId === null ||
        literAdded === null ||
        buyingPricePerLiter === null
    )
        return fail(c, 422, "Data restok tidak valid atau tidak lengkap.");

    const product = await c.env.DB.prepare(
        "SELECT id, current_stock, capacity, buying_price_per_liter FROM products WHERE id = ?",
    )
        .bind(productId)
        .first();
    if (!product) return fail(c, 404, "Produk BBM tidak ditemukan.");
    const addedLiters = liters(literAdded);
    if (Number(product.current_stock) + addedLiters > Number(product.capacity))
        return fail(
            c,
            409,
            `Pengisian melebihi kapasitas tangki ${product.capacity} liter.`,
        );

    const oldStock = Number(product.current_stock) || 0;
    const oldCost = Number(product.buying_price_per_liter) || 0;
    const newStock = oldStock + addedLiters;
    const weightedAverageCost = ((oldStock * oldCost) + (addedLiters * buyingPricePerLiter)) / newStock;
    const totalCost = addedLiters * buyingPricePerLiter;

    await c.env.DB.batch([
        c.env.DB.prepare(
            "INSERT INTO stock_refills (product_id, liter_added, buying_price_per_liter, total_cost, supplier_note, created_at, updated_at) VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
        ).bind(productId, addedLiters, money(buyingPricePerLiter), money(totalCost), supplierNote || null),
        c.env.DB.prepare(
            "UPDATE products SET current_stock = ?, buying_price_per_liter = ?, updated_at = datetime('now') WHERE id = ?",
        ).bind(liters(newStock), money(weightedAverageCost), productId),
    ]);

    return c.json(
        { success: true, data: { productId, literAdded: addedLiters, totalCost: money(totalCost), weightedAverageCost: money(weightedAverageCost), currentStock: liters(newStock) } },
        201,
    );
});

app.onError((exception, c) => {
    console.error("D1 bridge request failed", exception);
    return fail(c, 500, "Terjadi kesalahan internal pada D1 bridge.");
});

export default app;
