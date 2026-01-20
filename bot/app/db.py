import aiomysql


class Database:
    def __init__(self, config: dict):
        self._config = config
        self._pool = None

    async def connect(self) -> None:
        self._pool = await aiomysql.create_pool(
            host=self._config["db"]["host"],
            port=self._config["db"]["port"],
            user=self._config["db"]["user"],
            password=self._config["db"]["password"],
            db=self._config["db"]["name"],
            autocommit=True,
        )

    async def close(self) -> None:
        if self._pool:
            self._pool.close()
            await self._pool.wait_closed()

    async def fetchone(self, query: str, params: tuple = ()):
        async with self._pool.acquire() as conn:
            async with conn.cursor(aiomysql.DictCursor) as cur:
                await cur.execute(query, params)
                return await cur.fetchone()

    async def fetchall(self, query: str, params: tuple = ()):
        async with self._pool.acquire() as conn:
            async with conn.cursor(aiomysql.DictCursor) as cur:
                await cur.execute(query, params)
                return await cur.fetchall()

    async def execute(self, query: str, params: tuple = ()):
        async with self._pool.acquire() as conn:
            async with conn.cursor() as cur:
                await cur.execute(query, params)

    async def transaction(self, queries: list[tuple[str, tuple]]):
        async with self._pool.acquire() as conn:
            async with conn.cursor() as cur:
                await conn.begin()
                try:
                    for query, params in queries:
                        await cur.execute(query, params)
                    await conn.commit()
                except Exception:
                    await conn.rollback()
                    raise
