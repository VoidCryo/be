<?php

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

Route::prefix("users")->group(function () {
    Route::get("", function () {
        try {
            $users = User::query()->get();

            return response()->json(
                [
                    "message" => "Users retrieved successfully",
                    "data" => $users,
                ],
                Response::HTTP_OK,
            );
        } catch (\Throwable $e) {
        }
    });

    Route::post("", function (Request $request) {
        try {
            $validated = $request->validate([
                "username" => [
                    "required",
                    "string",
                    "max:255",
                    "unique:users,username",
                ],
                "email" => [
                    "required",
                    "email",
                    "max:255",
                    "unique:users,email",
                ],
                "password" => ["required", "string", "min:8"],
            ]);

            $user = User::query()->create([
                "username" => $validated["username"],
                "email" => $validated["email"],
                "password" => Hash::make($validated["password"]),
            ]);

            return response()->json(
                [
                    "message" => "User created successfully",
                    "data" => $user,
                ],
                Response::HTTP_CREATED,
            );
        } catch (\Throwable $e) {
        }
    });

    Route::prefix("{id}")
        ->whereNumber("id")
        ->group(function () {
            Route::get("", function (int $id) {
                try {
                    $user = User::query()->findOrFail($id);

                    return response()->json(
                        [
                            "message" => "User retrieved successfully",
                            "data" => $user,
                        ],
                        Response::HTTP_OK,
                    );
                } catch (ModelNotFoundException $e) {
                    return response()->json(
                        [
                            "message" => "User not found",
                        ],
                        Response::HTTP_NOT_FOUND,
                    );
                } catch (\Throwable $e) {
                }
            });

            Route::patch("", function (Request $request, int $id) {
                try {
                    $user = User::query()->findOrFail($id);

                    $validated = $request->validate([
                        "username" => [
                            "sometimes",
                            "required",
                            "string",
                            "max:255",
                            Rule::unique("users", "username")->ignore(
                                $user->id,
                            ),
                        ],
                        "email" => [
                            "sometimes",
                            "required",
                            "email",
                            "max:255",
                            Rule::unique("users", "email")->ignore($user->id),
                        ],
                        "password" => [
                            "sometimes",
                            "required",
                            "string",
                            "min:8",
                        ],
                    ]);

                    if (array_key_exists("password", $validated)) {
                        $validated["password"] = Hash::make(
                            $validated["password"],
                        );
                    }

                    $user->update($validated);

                    return response()->json(
                        [
                            "message" => "User updated successfully",
                            "data" => $user->fresh(),
                        ],
                        Response::HTTP_OK,
                    );
                } catch (ModelNotFoundException $e) {
                    return response()->json(
                        [
                            "message" => "User not found",
                        ],
                        Response::HTTP_NOT_FOUND,
                    );
                } catch (\Throwable $e) {
                }
            });

            Route::delete("", function (int $id) {
                try {
                    $user = User::query()->findOrFail($id);
                    $user->delete();

                    return response()->noContent();
                } catch (ModelNotFoundException $e) {
                    return response()->json(
                        [
                            "message" => "User not found",
                        ],
                        Response::HTTP_NOT_FOUND,
                    );
                } catch (\Throwable $e) {
                }
            });
        });
});
