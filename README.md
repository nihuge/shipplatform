﻿## 简介
计量平台一起更新版

1：更换前端模板；
2：新增固体货物计量；
3：公司新增评价体系；
	3.1：只有检验公司的做的作业才可以进行互相评价；
	3.2：单次作业记录评价，同时修改船舶评价、操作员评价等级；
	3.3：公司记录评价的总分、评价的次数
4：数据库手动备份；
5：前端新增检验公司查询、船舶查询、船公司查询；
6：公司新增带logo；
7：存证功能：每一项观测输入的数据均可拍照存证；
8：船基础数据添加、公司基础数据添加；
9：推送平台通知公告等消息；
10：船舶作业分析；
11：新建船舶根据算法新建对象表结构；
12：公司历史总吨位，总次数根据公司下面所有船的历史总吨位、总作业次数总和计算；
13：添加可查看本公司所有的作业；




9月13日讨论结果：
1：检验公司与船舶公司对同一次作业分别新建一次作业，统计公司的总货重、总作业次数，同一条船在24小时内新建的作业不会累计统计货重与次数；
2：评价体系
	2.1：查询列表显示所有自己可以查询权限的船舶作业（自己新建的作业+检验公司新建的改船舶作业）
	2.2：列表颜色区分自己的作业与评价的作业;
	2.3：互相评价不管先后；
	2.4：没有互相评价不显示对方评价信息；
	2.5：作业有电子签证证明作业完成，可以进行评价；
	2.6：查询列表执行超时自动评价功能；


存在问题：
	1.关于app传基准高度错误问题:
		1：measure数据录入接口；
		2：reckon计算接口；
