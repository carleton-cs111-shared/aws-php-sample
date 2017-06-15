import sys;

for i in range(1000):
    print(i, end=' ');
    if i % 100 == 0:
        print();
    if i % 200 == 0:
        print("mod 200 is 0:" + str(i), file=sys.stderr)
print();
print('[end]');
