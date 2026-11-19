import { Elysia, problem } from "elysia";
import { env } from "./env";
import { db } from "./libs/databases/db";
import { UserModel } from "./routes/user/user_model";
import { Responses } from "./libs/response";
import { users } from "./libs/databases/schema";
import z from "zod";
import { eq } from "drizzle-orm";

const index = new Elysia({ prefix: "/api" })
  .get("/", () => "Hello Elysia")
  .group("/users", (app) =>
    app
      .get("", async () => {
        const data = await db.query.users.findMany();
        return Responses.Ok(data, "Finding all users");
      })
      .post("", { body: UserModel.create }, async ({ body }) => {
        const data = await db.insert(users).values(body).returning();
        return Responses.Create(data, "Creating new user");
      })

      .group(
        "/:id",
        {
          params: z.object({ id: z.coerce.number() }),
        },
        (app) =>
          app
            .get("", async ({ params: { id } }) => {
              const data = await db.query.users.findFirst({
                where: { id: { eq: id } },
              });
              if (!data) {
                throw problem("Not Found", {
                  detail: `User by id: ${id}, not found`,
                });
              }
              return Responses.Ok(data, `Finding user by id: ${id}`);
            })
            .patch(
              "",
              { body: UserModel.update },
              async ({ params: { id }, body }) => {
                const data = db
                  .update(users)
                  .set(body)
                  .where(eq(users.id, id))
                  .returning();
                if (!data) {
                  throw problem("Not Found", {
                    detail: `User by id: ${id}, not found`,
                  });
                }
                return Responses.Ok(data, `Updating user for id: ${id}`);
              },
            )
            .delete("", async ({ params: { id } }) => {
              const data = await db
                .delete(users)
                .where(eq(users.id, id))
                .returning();
              if (!data) {
                throw problem("Not Found", {
                  detail: `User by id: ${id}, not found`,
                });
              }
              return problem("No Content", { detail: "no user found" });
            }),
      ),
  )
  .listen(env.PORT);

console.log(
  `🦊 Elysia is running at ${index.server?.hostname}:${index.server?.port}`,
);
