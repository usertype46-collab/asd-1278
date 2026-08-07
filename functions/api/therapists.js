// Cloudflare Pages / Edge Route: /api/clients

export async function onRequestGet(context) {
    const { env } = context;
    try {
        const { results } = await env.DB.prepare("SELECT * FROM clients ORDER BY id DESC").all();
        return new Response(JSON.stringify(results), {
            headers: { "Content-Type": "application/json" }
        });
    } catch (err) {
        return new Response(JSON.stringify({ error: err.message }), { status: 500 });
    }
}

export async function onRequestPost(context) {
    const { request, env } = context;
    const body = await request.json();
    const { name, phone, total_visits } = body;

    try {
        const insertResult = await env.DB.prepare(
            "INSERT INTO clients (name, phone, total_visits) VALUES (?, ?, ?) RETURNING id"
        ).bind(name, phone || '', total_visits || 1).first();

        return new Response(JSON.stringify({ success: true, id: insertResult.id }), { status: 201 });
    } catch (err) {
        return new Response(JSON.stringify({ error: err.message }), { status: 500 });
    }
}

export async function onRequestPut(context) {
    const { request, env } = context;
    const body = await request.json();
    const { id, name, phone, total_visits } = body;

    try {
        await env.DB.prepare(
            "UPDATE clients SET name = ?, phone = ?, total_visits = ? WHERE id = ?"
        ).bind(name, phone, total_visits, id).run();

        return new Response(JSON.stringify({ success: true }), { status: 200 });
    } catch (err) {
        return new Response(JSON.stringify({ error: err.message }), { status: 500 });
    }
}
