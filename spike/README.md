# LibContainerSpike (스파이크)

블록 없이, 패킷으로만 만든 투명 엔티티를 컨테이너 소유자로 삼아 Bedrock 클라이언트에서 인벤토리 창이 열리는지 확인하는 테스트용 PocketMine-MP 플러그인입니다.
(PocketMine-MP 5.51.x / API 5.51.0, 프로토콜 64 기준. PocketMine-MP 및 BedrockProtocol 소스만 참고해 작성)

## 동작 방식
1. `/lcs open` 실행 시 `SpikeInventory`를 `setCurrentWindow()`로 연다.
2. `InventoryManager::getContainerOpenCallbacks()`에 등록한 클로저가 `AddActorPacket`(가짜 엔티티) + `ContainerOpenPacket::entityInv()`를 반환한다.
   - 메타데이터: INVISIBLE, IMMOBILE, NAMETAG(=title), SCALE, 바운딩박스(scale<0.5면 0), CONTAINER_TYPE, CONTAINER_BASE_SIZE
3. 창이 닫히면(InventoryCloseEvent) / 퇴장 시 `RemoveActorPacket` 전송.

## 설치
- 폴더 플러그인: `libcontainer-spike/` 폴더를 서버의 `plugins/`에 `LibContainerSpike` 이름으로 복사 (DevTools 또는 폴더 로더 필요).
- phar: `LibContainerSpike.phar`를 `plugins/`에 넣고 서버 재시작.
- phar 재빌드: `php -d phar.readonly=0 build.php`

## 명령어 (권한: OP)
- `/lcs open <entityType> <size> [windowType=0] [title...]`
  - 예: `/lcs open minecraft:chest_minecart 27`
  - 예: `/lcs open minecraft:npc 54 0 §b§wtest`
  - 슬롯마다 종이("slot N", 개수 = N%64+1)가 채워져 몇 칸이 렌더링되는지 확인 가능
- `/lcs opt <scale|visible|distance|dy> <값>`
  - `scale` 기본 0.01 (1로도 시험), `visible` 0/1 (1이면 엔티티가 보임), `distance` 기본 1.5, `dy` 기본 -0.5 (눈 높이 기준)
  - 인자 없이 `/lcs opt` 하면 현재값 표시 (인자 3개 미만)
- `/lcs log` 로그 on/off 토글

windowType 값: 0=CONTAINER, 10=MINECART_CHEST, 8=HOPPER, 12=HORSE 등 (`WindowTypes` 참고).

## 확인할 것 / 로그
서버 콘솔과 채팅(§7[lcs])에 동시에 출력됩니다.
- 송신: AddActor(타입/ID/좌표/scale), ContainerOpen(windowId/type/size)
- 수신: `ContainerClose windowId=...` - 열자마자 바로 오면 클라이언트가 창을 닫은 것. `-1/255`면 창 표시 거부.
- 슬롯 탭 / Shift+클릭 / 드래그 / 버리기 시 트랜잭션: 각 SlotChangeAction의 인벤토리 클래스, 슬롯, 원본/대상 아이템 (SpikeInventory는 `<== SPIKE` 표시). 트랜잭션은 취소됩니다.
- InventoryCloseEvent, RemoveActor 송신

## 시험 시나리오 제안
1. `minecraft:chest_minecart 27` (scale 0.01) - 창이 열리는지, 27칸 모두 보이는지
2. `/lcs opt scale 1` + `/lcs opt visible 1` 로 엔티티를 보이게 하고 위치/렌더 확인
3. `minecraft:npc 54`, `minecraft:horse` 등 다른 타입, `windowType` 변경
4. 탭/Shift/드래그/드롭 로그 비교
