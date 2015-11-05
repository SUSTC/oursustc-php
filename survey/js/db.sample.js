
var survey = {
  id: 1,
  title: '计算机社信息统计',
  description: '统计相关信息，请认真回答<br>大家不用担心隐私泄露，我们全程使用SSL连接并保证不会主动将数据泄露给第三者，感谢大家的支持<br><br>我们主要的活动内容是研究编程开发技术，暂定活动时间为周六，地点请等待进一步通知。<br>本学期的主要目标为提高新手开发技术、本社团内网站开发。',
  question: {
    name: {
      name: "姓名",
      unique: true,
      require: true,
      type: 'string',
      placeholder: '姓名',
      default: ''
    },
    studentid: {
      name: '学号',
      unique: true,
      require: true,
      type: 'number',
      placeholder: '学号',
      default: 11310000
    },
    email: {
      name: '邮箱',
      require: true,
      type: 'string',
      placeholder: '常用邮箱',
      help: '请填写自己的常用邮箱，并能在 1 天内查看并处理该邮箱中的邮件，将用于接受本社团的通知信息。',
      default: ''
    },
    phone: {
      name: '联系电话',
      require: true,
      type: 'number',
      placeholder: '联系电话',
      help: '请填写自己的常用联系电话，并能在邮件无法送达时能使用该电话接收到通知。',
      default: ''
    },
    feel: {
      name: '自己对于计算机的感觉',
      type: 'select',
      multiple: false,
      option: [
        "从来没学过或感觉不怎么会操作",
        "会操作一些常用软件，如 Office、Photoshop 等",
        "可以熟练重装系统",
        "经常编程来减少乏味地机械重复操作",
        "参与过5人及5人以上的大型项目开发",
        "前面的选项都弱爆了"
      ],
      placeholder: 'Feel',
      default: 1
    },
    hardware: {
      name: '关于计算机硬件',
      type: 'select',
      multiple: false,
      option: [
        "对于计算机硬件不怎么了解",
        "大致了解计算机的硬件构成",
        "自己可以组装台式机",
        "熟悉一些计算机硬件品牌，并能说出一些同产品间的参数差距带来的结果",
        "熟悉一些计算机芯片型号，能比较其性能及功能的差异",
        "前面的选项都弱爆了"
      ],
      placeholder: 'Hardware',
      default: 1
    },
    os: {
      name: "熟悉或基本可以使用的<br>操作系统",
      type: 'select',
      multiple: true,
      option: [
        "Microsoft Windows",
        "Mac OS X",
        "Linux/Unix (Debian, Ubuntu, openSUSE, BSD 等)",
        "自己开发过操作系统",
        "其他"
      ],
      placeholder: 'OS',
      default: [0]
    },
    programming: {
      name: "熟悉或基本可以使用的<br>编程语言",
      type: 'select',
      multiple: true,
      option: [
        "C/C++/Obj-C",
        "Delphi/Pascal",
        "Go",
        "Lisp",
        "Java",
        "JavaScript",
        "PHP",
        "Python",
        "Ruby",
        "Microsoft Visual Basic",
        "Microsoft C#",
        "汇编",
        "自己开发过编程语言",
        "其他"
      ],
      placeholder: 'Programming',
      default: []
    },
    weeklyuse: {
      name: "每周使用计算机时间",
      type: 'select',
      multiple: false,
      option: [
        "少于 7 小时",
        "7 小时到 14 小时",
        "14 小时到 28 小时",
        "28 小时到 42 小时",
        "42 小时以上"
      ],
      placeholder: '每周使用时间',
      default: 1
    },
    howtouse: {
      name: "平时如何使用计算机",
      type: 'select',
      multiple: true,
      option: [
        "工作学习查阅资料等",
        "编程开发",
        "刷社交网站",
        "使用即时通讯工具聊天",
        "处理邮件",
        "浏览新闻、资讯以及订阅网站等",
        "录入文本、处理信息等 (使用 Office、Photoshop 等)",
		"购物",
        "炒股",
        "其他"
      ],
      placeholder: '每周使用时间',
      default: [0, 2, 3]
    },
    sns: {
      name: "常用社交网站",
      type: 'select',
      multiple: true,
      inline: true,
      option: [
        "Facebook",
        "Google+",
        "Twitter",
        "QQ空间",
        "百度贴吧",
        "人人网",
        "腾讯微博",
        "新浪微博",
        "其他"
      ],
      placeholder: 'SNS',
      default: []
    },
    fee: {
      name: "社团费用",
      type: 'select',
      multiple: false,
      option: [
        "无",
        "20元",
        "50元",
        "100元",
        "100元以上",
      ],
      help: "由于社团部给的经费较少，我们需要租用或在学校自己搭建服务器（如果有独立许可使用的教室）等社团公共开销，故可能需要收取一定的社团费用。<br>另外，还可能会根据最后留下来的人数进行调整。",
      placeholder: '社团费用',
      default: 2
    },
	advice: {
      name: "建议",
      type: 'text',
	  placeholder: '给我们提供一些建议',
      default: ''
    },
    sustcitemail: {
      name: "申请社团邮箱",
      type: 'boolean',
      help: '我们社团可以给大家提供 @sustc.me 和 @sustc.us 的邮箱，如果大家想要的话可以申请格式为:<br> <b>peng.zt12@sustc.me 和 peng.zt12@sustc.us</b> 这种格式的邮箱。',
      default: false
    },
    agreement: {
      name: "同意南方科技大学学生社团章程",
      require: true,
      type: 'boolean',
      default: false
    }
  }
};

